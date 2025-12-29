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
use App\Repositories\Interfaces\LessonRepositoryInterface;
use App\Models\Lesson;

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
                'xlsx_file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $file = $request->file('xlsx_file');
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
                'class_code' => 'required|string|max:255',
                'teacher_id' => 'required|exists:teachers,id',
                'class_name' => 'nullable|string|max:255',
                'is_active' => 'nullable|boolean',
                'semester' => 'nullable|string|max:255',
                'max_students' => 'nullable|integer|min:1',
                'academic_year' => 'nullable|string|max:255'
            ]);

            $class = ClassModel::create($request->only([
                'class_code',
                'class_name',
                'teacher_id',
                'is_active',
                'semester',
                'max_students',
                'academic_year'
            ]));

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
                'classes.*.class_code' => 'required|string|max:255',
                'classes.*.teacher_id' => 'required|exists:teachers,id'
            ]);

            DB::beginTransaction();

            $createdClasses = [];
            foreach ($request->classes as $classData) {
                $class = ClassModel::create([
                    'class_code' => $classData['code'],
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
     * Update a class
     */
    public function updateClass(Request $request, ClassModel $classModel)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'class_code' => 'nullable|string|max:255',
                'class_name' => 'nullable|string|max:255',
                'teacher_id' => 'nullable|exists:teachers,id',
                'is_active' => 'nullable|boolean',
                'semester' => 'nullable|string|max:255',
                'max_students' => 'nullable|integer|min:1',
                'academic_year' => 'nullable|string|max:255'
            ]);

            $classModel->update($request->only([
                'class_code',
                'class_name',
                'teacher_id',
                'is_active',
                'semester',
                'max_students',
                'academic_year'
            ]));

            return response()->json([
                'data' => $classModel->load('teacher.user'),
                'message' => 'Class updated successfully',
                'success' => true,
                'remark' => 'Class information updated'
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
                'message' => 'Failed to update class',
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
                'class_ids.*' => 'exists:classes,id'
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
                'class_id' => 'required|exists:classes,id',
                'student_code'=>'nullable|string'
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
                'class_id' => $request->class_id,
                'student_code'=>$request->student_code
            ]);

            DB::commit();

            return response()->json([
                'data' => $student->load('user', 'classModel'), 
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
                'xlsx_file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $file = $request->file('xlsx_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $createdStudents = [];
            $errors = [];

            // Skip header row
            array_shift($rows);

            // Pre-check all emails to avoid partial failures
            $emails = array_column($rows, 0);
            $existingEmails = User::whereIn('email', $emails)
                ->pluck('email')
                ->toArray();

            DB::beginTransaction();

            // Process in chunks of 100
            $chunks = array_chunk($rows, 100);

            foreach ($chunks as $chunkIndex => $chunk) {
                $usersToInsert = [];
                $studentsToInsert = [];
                $now = now();

                foreach ($chunk as $rowIndex => $row) {
                    $email = $row[0] ?? null;
                    $password = $row[1] ?? '1';
                    $name = $row[2] ?? null;
                    $studentCode = $row[3] ?? null;
                    $classId = $row[4] ?? null;

                    $actualIndex = ($chunkIndex * 100) + $rowIndex + 2; // +2 for header and 0-index

                    if (!$email || !$name) {
                        $errors[] = "Row $actualIndex: Missing email or name";
                        continue;
                    }

                    if (in_array($email, $existingEmails)) {
                        $errors[] = "Row $actualIndex: Email $email already exists";
                        continue;
                    }

                    if ($classId && !ClassModel::where('id', $classId)->exists()) {
                        $errors[] = "Row $actualIndex: Class ID $classId does not exist";
                        continue;
                    }

                    $usersToInsert[] = [
                        'email' => $email,
                        'name' => $name,
                        'password' => Hash::make($password),
                        'role' => 'student',
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Store class_id and student_code for later
                    $createdStudents[] = [
                        'email' => $email,
                        'class_id' => $classId,
                        'student_code' => $studentCode,
                    ];
                }

                if (!empty($usersToInsert)) {
                    // Bulk insert users
                    User::insert($usersToInsert);

                    // Get inserted user IDs
                    $insertedUsers = User::whereIn('email', array_column($usersToInsert, 'email'))
                        ->orderBy('id')
                        ->get()
                        ->keyBy('email');

                    // Prepare student records
                    foreach ($createdStudents as $index => $studentData) {
                        if (isset($insertedUsers[$studentData['email']])) {
                            $studentsToInsert[] = [
                                'user_id' => $insertedUsers[$studentData['email']]->id,
                                'class_id' => $studentData['class_id'],
                                'student_code' => $studentData['student_code'],
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    // Bulk insert students
                    if (!empty($studentsToInsert)) {
                        Student::insert($studentsToInsert);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'data' => [
                    'total_created' => count($createdStudents) - count($errors),
                    'total_errors' => count($errors),
                    'errors' => $errors,
                ],
                'message' => 'Batch student creation completed',
                'success' => true,
                'remark' => (count($createdStudents) - count($errors)) . ' students created, ' . count($errors) . ' errors'
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
            $students = Student::with('user', 'classModel')->get();

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

    // ==========================================
    // LESSON MANAGEMENT
    // ==========================================

    /**
     * Create a new lesson
     */
    public function createLesson(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'topic_id' => 'required|exists:topics,id',
                'lesson_title' => 'required|string|max:255',
                'lesson_content' => 'required|string',
                'estimated_time' => 'nullable|numeric',
                'order_index' => 'required|numeric',
                'is_active' => 'boolean'
            ]);

            $lessonRepository = app(\App\Repositories\Interfaces\LessonRepositoryInterface::class);
            
            $lesson = $lessonRepository->create($request->only([
                'topic_id',
                'lesson_title',
                'lesson_content',
                'estimated_time',
                'order_index',
                'is_active'
            ]));

            return response()->json([
                'data' => $lesson,
                'message' => 'Lesson created successfully',
                'success' => true,
                'remark' => 'New lesson record created'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to create lesson',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a lesson
     */
    public function updateLesson(Request $request, Lesson $lesson)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'topic_id' => 'nullable|exists:topics,id',
                'lesson_title' => 'nullable|string|max:255',
                'lesson_content' => 'nullable|string',
                'estimated_time' => 'nullable|numeric',
                'order_index' => 'nullable|numeric',
                'is_active' => 'boolean'
            ]);

            $lessonRepository = app(\App\Repositories\Interfaces\LessonRepositoryInterface::class);
            
            $lesson = $lessonRepository->update(
                $lesson->id,
                $request->only([
                    'topic_id',
                    'lesson_title',
                    'lesson_content',
                    'estimated_time',
                    'order_index',
                    'is_active'
                ])
            );

            if ($lesson === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson not found',
                    'success' => false,
                    'remark' => 'The requested lesson does not exist'
                ], 404);
            }

            return response()->json([
                'data' => $lesson,
                'message' => 'Lesson updated successfully',
                'success' => true,
                'remark' => 'Lesson record updated'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to update lesson',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lesson
     */
    public function deleteLesson(Request $request, Lesson $lesson)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $lessonRepository = app(\App\Repositories\Interfaces\LessonRepositoryInterface::class);
            
            $deleted = $lessonRepository->delete($lesson->id);

            if (!$deleted) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson not found',
                    'success' => false,
                    'remark' => 'The requested lesson does not exist'
                ], 404);
            }

            return response()->json([
                'data' => null,
                'message' => 'Lesson deleted successfully',
                'success' => true,
                'remark' => 'Lesson record deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete lesson',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // LESSON EXERCISE MANAGEMENT
    // ==========================================

    /**
     * Create a new lesson exercise
     */
    public function createLessonExercise(Request $request)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'lesson_id' => 'required|exists:lessons,id',
                'exercise_title' => 'nullable|string|max:255',
                'is_active' => 'boolean'
            ]);

            $lessonExerciseRepository = app(\App\Repositories\Interfaces\LessonExerciseRepositoryInterface::class);
            
            $exercise = $lessonExerciseRepository->create([
                'lesson_id' => $request->lesson_id,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'data' => $exercise,
                'message' => 'Lesson exercise created successfully',
                'success' => true,
                'remark' => 'New lesson exercise record created'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to create lesson exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a lesson exercise
     */
    public function updateLessonExercise(Request $request, $exerciseId)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $request->validate([
                'lesson_id' => 'nullable|exists:lessons,id',
                'is_active' => 'boolean'
            ]);

            $lessonExerciseRepository = app(\App\Repositories\Interfaces\LessonExerciseRepositoryInterface::class);
            
            $exercise = $lessonExerciseRepository->update(
                $exerciseId,
                $request->only(['lesson_id', 'is_active'])
            );

            if ($exercise === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise not found',
                    'success' => false,
                    'remark' => 'The requested lesson exercise does not exist'
                ], 404);
            }

            return response()->json([
                'data' => $exercise,
                'message' => 'Lesson exercise updated successfully',
                'success' => true,
                'remark' => 'Lesson exercise record updated'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to update lesson exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lesson exercise
     */
    public function deleteLessonExercise(Request $request, $exerciseId)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            $lessonExerciseRepository = app(\App\Repositories\Interfaces\LessonExerciseRepositoryInterface::class);
            
            $deleted = $lessonExerciseRepository->delete($exerciseId);

            if (!$deleted) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise not found',
                    'success' => false,
                    'remark' => 'The requested lesson exercise does not exist'
                ], 404);
            }

            return response()->json([
                'data' => null,
                'message' => 'Lesson exercise deleted successfully',
                'success' => true,
                'remark' => 'Lesson exercise record deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete lesson exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getClass(Request $request, ClassModel $classModel)
    {
        $adminCheck = $this->verifyAdmin($request);
        if ($adminCheck instanceof \Illuminate\Http\JsonResponse) {
            return $adminCheck;
        }

        try {
            return response()->json([
                'data' => $classModel->load('teacher.user'),
                'message' => 'Class retrieved successfully',
                'success' => true,
                'remark' => 'Class details with teacher information'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve class',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
}
