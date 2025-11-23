<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\ClassEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminController extends Controller
{
    /**
     * Verify admin authorization
     */
    private function verifyAdmin(Request $request)
    {
        $admin = Admin::where('user_id', $request->user()->id)->first();
        
        if (!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can perform this action'
            ], 403);
        }
        
        return $admin;
    }

    // ==========================================
    // TEACHER MANAGEMENT
    // ==========================================

    /**
     * Create a single teacher
     */
    public function createTeacher(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'name' => 'required|string|max:255',
            ]);

            DB::beginTransaction();

            $user = User::create([
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : Hash::make("1"),
                'name' => $request->name,
                'role' => 'teacher',
                'is_active' => true,
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id
            ]);

            DB::commit();

            return response()->json([
                'data' => $teacher->load('user'),
                'message' => 'Teacher added successfully',
                'success' => true,
                'remark' => 'New teacher user and record created'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to add teacher',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch create teachers from Excel/CSV file
     */
    public function batchCreateTeachers(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $createdTeachers = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                // Skip header row
                if ($index === 0) continue;

                $email = $row[0] ?? null;
                $name = $row[1] ?? null;
                $password = $row[2] ?? '1';

                if (!$email || !$name) {
                    $errors[] = "Row " . ($index + 1) . ": Missing email or name";
                    continue;
                }

                // Check if user already exists
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Row " . ($index + 1) . ": Email $email already exists";
                    continue;
                }

                try {
                    $user = User::create([
                        'email' => $email,
                        'name' => $name,
                        'password' => Hash::make($password),
                        'role' => 'teacher',
                        'is_active' => true,
                    ]);

                    $teacher = Teacher::create([
                        'user_id' => $user->id
                    ]);

                    $createdTeachers[] = $teacher;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'data' => [
                    'created' => $createdTeachers,
                    'errors' => $errors,
                    'total_created' => count($createdTeachers),
                    'total_errors' => count($errors)
                ],
                'message' => 'Batch teacher creation completed',
                'success' => true,
                'remark' => count($createdTeachers) . ' teachers created, ' . count($errors) . ' errors'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to process batch teachers',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all teachers
     */
    public function getTeachers(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $teachers = Teacher::with('user')->get();

            return response()->json([
                'data' => $teachers,
                'message' => 'Teachers retrieved successfully',
                'success' => true,
                'remark' => 'List of all teachers with user information'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve teachers',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a teacher
     */
    public function deleteTeacher(Request $request, Teacher $teacher)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            DB::beginTransaction();

            $user = $teacher->user;
            $teacher->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'data' => null,
                'message' => 'Teacher deleted successfully',
                'success' => true,
                'remark' => 'Teacher and associated user removed'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete teacher',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch delete teachers
     */
    public function batchDeleteTeachers(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'teacher_ids' => 'required|array',
                'teacher_ids.*' => 'exists:teachers,id'
            ]);

            DB::beginTransaction();

            $teachers = Teacher::whereIn('id', $request->teacher_ids)->with('user')->get();
            $deletedCount = 0;

            foreach ($teachers as $teacher) {
                $user = $teacher->user;
                $teacher->delete();
                $user->delete();
                $deletedCount++;
            }

            DB::commit();

            return response()->json([
                'data' => ['deleted_count' => $deletedCount],
                'message' => 'Teachers deleted successfully',
                'success' => true,
                'remark' => "$deletedCount teachers and their users removed"
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete teachers',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // CLASS MANAGEMENT
    // ==========================================

    /**
     * Create a class
     */
    public function createClass(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'teacher_id' => 'required|exists:teachers,id'
            ]);

            $class = ClassModel::create([
                'name' => $request->name,
                'teacher_id' => $request->teacher_id
            ]);

            return response()->json([
                'data' => $class->load('teacher.user'),
                'message' => 'Class created successfully',
                'success' => true,
                'remark' => 'New class created and assigned to teacher'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to create class',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk create classes
     */
    public function bulkCreateClasses(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'classes' => 'required|array',
                'classes.*.name' => 'required|string|max:255',
                'classes.*.teacher_id' => 'required|exists:teachers,id'
            ]);

            DB::beginTransaction();

            $createdClasses = [];
            foreach ($request->classes as $classData) {
                $class = ClassModel::create([
                    'name' => $classData['name'],
                    'teacher_id' => $classData['teacher_id']
                ]);
                $createdClasses[] = $class;
            }

            DB::commit();

            return response()->json([
                'data' => $createdClasses,
                'message' => 'Classes created successfully',
                'success' => true,
                'remark' => count($createdClasses) . ' classes created'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to create classes',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all classes
     */
    public function getClasses(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $classes = ClassModel::with('teacher.user')->get();

            return response()->json([
                'data' => $classes,
                'message' => 'Classes retrieved successfully',
                'success' => true,
                'remark' => 'List of all classes with teacher information'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve classes',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a class
     */
    public function deleteClass(Request $request, ClassModel $classModel)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $classModel->delete();

            return response()->json([
                'data' => null,
                'message' => 'Class deleted successfully',
                'success' => true,
                'remark' => 'Class removed from system'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete class',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch delete classes
     */
    public function batchDeleteClasses(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'class_ids' => 'required|array',
                'class_ids.*' => 'exists:class_models,id'
            ]);

            $deletedCount = ClassModel::whereIn('id', $request->class_ids)->delete();

            return response()->json([
                'data' => ['deleted_count' => $deletedCount],
                'message' => 'Classes deleted successfully',
                'success' => true,
                'remark' => "$deletedCount classes removed"
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete classes',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // STUDENT MANAGEMENT
    // ==========================================

    /**
     * Create a student
     */
    public function createStudent(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'name' => 'required|string|max:255',
                'class_id' => 'nullable|exists:class_models,id'
            ]);

            DB::beginTransaction();

            $user = User::create([
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : Hash::make("1"),
                'name' => $request->name,
                'role' => 'student',
                'is_active' => true,
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'class_id' => $request->class_id
            ]);

            // If class is assigned, create enrollment
            if ($request->class_id) {
                ClassEnrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $request->class_id
                ]);
            }

            DB::commit();

            return response()->json([
                'data' => $student->load('user', 'class'),
                'message' => 'Student added successfully',
                'success' => true,
                'remark' => 'New student user and record created'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to add student',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch create students from Excel/CSV
     */
    public function batchCreateStudents(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $createdStudents = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                // Skip header row
                if ($index === 0) continue;

                $email = $row[0] ?? null;
                $name = $row[1] ?? null;
                $password = $row[2] ?? '1';
                $classId = $row[3] ?? null;

                if (!$email || !$name) {
                    $errors[] = "Row " . ($index + 1) . ": Missing email or name";
                    continue;
                }

                // Check if user already exists
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Row " . ($index + 1) . ": Email $email already exists";
                    continue;
                }

                try {
                    $user = User::create([
                        'email' => $email,
                        'name' => $name,
                        'password' => Hash::make($password),
                        'role' => 'student',
                        'is_active' => true,
                    ]);

                    $student = Student::create([
                        'user_id' => $user->id,
                        'class_id' => $classId
                    ]);

                    // If class is assigned, create enrollment
                    if ($classId) {
                        ClassEnrollment::create([
                            'student_id' => $student->id,
                            'class_id' => $classId
                        ]);
                    }

                    $createdStudents[] = $student;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'data' => [
                    'created' => $createdStudents,
                    'errors' => $errors,
                    'total_created' => count($createdStudents),
                    'total_errors' => count($errors)
                ],
                'message' => 'Batch student creation completed',
                'success' => true,
                'remark' => count($createdStudents) . ' students created, ' . count($errors) . ' errors'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to process batch students',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all students
     */
    public function getStudents(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $students = Student::with('user', 'class')->get();

            return response()->json([
                'data' => $students,
                'message' => 'Students retrieved successfully',
                'success' => true,
                'remark' => 'List of all students with user and class information'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve students',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a student
     */
    public function deleteStudent(Request $request, Student $student)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            DB::beginTransaction();

            $user = $student->user;
            $student->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'data' => null,
                'message' => 'Student deleted successfully',
                'success' => true,
                'remark' => 'Student and associated user removed'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete student',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch delete students
     */
    public function batchDeleteStudents(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'student_ids' => 'required|array',
                'student_ids.*' => 'exists:students,id'
            ]);

            DB::beginTransaction();

            $students = Student::whereIn('id', $request->student_ids)->with('user')->get();
            $deletedCount = 0;

            foreach ($students as $student) {
                $user = $student->user;
                $student->delete();
                $user->delete();
                $deletedCount++;
            }

            DB::commit();

            return response()->json([
                'data' => ['deleted_count' => $deletedCount],
                'message' => 'Students deleted successfully',
                'success' => true,
                'remark' => "$deletedCount students and their users removed"
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete students',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove student from class
     */
    public function removeStudentFromClass(Request $request, Student $student)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            DB::beginTransaction();

            // Remove class enrollment
            ClassEnrollment::where('student_id', $student->id)->delete();

            // Update student record
            $student->update(['class_id' => null]);

            DB::commit();

            return response()->json([
                'data' => $student->load('user'),
                'message' => 'Student removed from class successfully',
                'success' => true,
                'remark' => 'Student class assignment cleared'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to remove student from class',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch remove students from classes
     */
    public function batchRemoveStudentsFromClasses(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'student_ids' => 'required|array',
                'student_ids.*' => 'exists:students,id'
            ]);

            DB::beginTransaction();

            // Remove enrollments
            ClassEnrollment::whereIn('student_id', $request->student_ids)->delete();

            // Update student records
            $updatedCount = Student::whereIn('id', $request->student_ids)
                ->update(['class_id' => null]);

            DB::commit();

            return response()->json([
                'data' => ['updated_count' => $updatedCount],
                'message' => 'Students removed from classes successfully',
                'success' => true,
                'remark' => "$updatedCount students' class assignments cleared"
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to remove students from classes',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
