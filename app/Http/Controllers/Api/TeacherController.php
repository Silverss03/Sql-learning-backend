<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\TeacherRepositoryInterface;

class TeacherController extends Controller
{
    protected $teacherRepository;

    public function __construct(TeacherRepositoryInterface $teacherRepository)
    {
        $this->teacherRepository = $teacherRepository;
    }

    /**
     * Get all classes assigned to the authenticated teacher
     */
    public function getClasses(Request $request)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            $classes = $this->teacherRepository->getClasses($teacher->id);

            return response()->json([
                'data' => $classes,
                'message' => 'Classes retrieved successfully',
                'success' => true,
                'remark' => 'All classes assigned to the teacher'
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
     * Get a specific class by ID
     */
    public function getClass(Request $request, $classId)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            $class = $this->teacherRepository->getClassById($teacher->id, $classId);

            if (!$class) {
                return response()->json([
                    'data' => null,
                    'message' => 'Class not found or not assigned to you',
                    'success' => false,
                    'remark' => 'The requested class does not exist or does not belong to you'
                ], 404);
            }

            return response()->json([
                'data' => $class,
                'message' => 'Class retrieved successfully',
                'success' => true,
                'remark' => 'Class details with students'
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

    /**
     * Get all students in a specific class
     */
    public function getStudentsByClass(Request $request, $classId)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            // Verify the class belongs to the teacher
            $class = $this->teacherRepository->getClassById($teacher->id, $classId);

            if (!$class) {
                return response()->json([
                    'data' => null,
                    'message' => 'Class not found or not assigned to you',
                    'success' => false,
                    'remark' => 'You can only view students from your own classes'
                ], 403);
            }

            $students = $this->teacherRepository->getStudentsByClass($classId);

            return response()->json([
                'data' => $students,
                'message' => 'Students retrieved successfully',
                'success' => true,
                'remark' => 'All students in the specified class'
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
     * Get detailed progress of a specific student
     */
    public function getStudentProgress(Request $request, $studentId)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            $progress = $this->teacherRepository->getStudentProgress($studentId);

            return response()->json([
                'data' => $progress,
                'message' => 'Student progress retrieved successfully',
                'success' => true,
                'remark' => 'Detailed progress including lessons, chapters, and exams'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve student progress',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics for a specific class
     */
    public function getClassAnalytics(Request $request, $classId)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            // Verify the class belongs to the teacher
            $class = $this->teacherRepository->getClassById($teacher->id, $classId);

            if (!$class) {
                return response()->json([
                    'data' => null,
                    'message' => 'Class not found or not assigned to you',
                    'success' => false,
                    'remark' => 'You can only view analytics for your own classes'
                ], 403);
            }

            $analytics = $this->teacherRepository->getClassAnalytics($classId);

            return response()->json([
                'data' => $analytics,
                'message' => 'Class analytics retrieved successfully',
                'success' => true,
                'remark' => 'Comprehensive analytics for the class'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve class analytics',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all grades/scores for students in a class
     */
    public function getStudentGrades(Request $request, $classId)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            // Verify the class belongs to the teacher
            $class = $this->teacherRepository->getClassById($teacher->id, $classId);

            if (!$class) {
                return response()->json([
                    'data' => null,
                    'message' => 'Class not found or not assigned to you',
                    'success' => false,
                    'remark' => 'You can only view grades for your own classes'
                ], 403);
            }

            $grades = $this->teacherRepository->getStudentGrades($classId);

            return response()->json([
                'data' => $grades,
                'message' => 'Student grades retrieved successfully',
                'success' => true,
                'remark' => 'All student grades with breakdown by lesson, chapter, and exam'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve student grades',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all exams for a specific class
     */
    public function getExamsByClass(Request $request, $classId)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            // Verify the class belongs to the teacher
            $class = $this->teacherRepository->getClassById($teacher->id, $classId);

            if (!$class) {
                return response()->json([
                    'data' => null,
                    'message' => 'Class not found or not assigned to you',
                    'success' => false,
                    'remark' => 'You can only view exams for your own classes'
                ], 403);
            }

            $exams = $this->teacherRepository->getExamsByClass($classId);

            return response()->json([
                'data' => $exams,
                'message' => 'Exams retrieved successfully',
                'success' => true,
                'remark' => 'All exams for the specified class with statistics'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exams',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new exam for a class
     */
    public function createExam(Request $request)
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'duration_minutes' => 'required|integer|min:1',
                'total_questions' => 'required|integer|min:1',
                'is_active' => 'nullable|boolean',
            ]);

            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            // Verify the class belongs to the teacher
            $class = $this->teacherRepository->getClassById($teacher->id, $request->class_id);

            if (!$class) {
                return response()->json([
                    'data' => null,
                    'message' => 'Class not found or not assigned to you',
                    'success' => false,
                    'remark' => 'You can only create exams for your own classes'
                ], 403);
            }

            $exam = $this->teacherRepository->createExam($teacher->id, $request->all());

            return response()->json([
                'data' => $exam,
                'message' => 'Exam created successfully',
                'success' => true,
                'remark' => 'New exam has been created for the class'
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
                'message' => 'Failed to create exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing exam
     */
    public function updateExam(Request $request, $examId)
    {
        try {
            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'duration_minutes' => 'sometimes|required|integer|min:1',
                'total_questions' => 'sometimes|required|integer|min:1',
                'is_active' => 'nullable|boolean',
            ]);

            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            $exam = $this->teacherRepository->updateExam($examId, $request->all());

            return response()->json([
                'data' => $exam,
                'message' => 'Exam updated successfully',
                'success' => true,
                'remark' => 'Exam has been updated with new information'
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
                'message' => 'Failed to update exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an exam
     */
    public function deleteExam(Request $request, $examId)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            $this->teacherRepository->deleteExam($examId);

            return response()->json([
                'data' => null,
                'message' => 'Exam deleted successfully',
                'success' => true,
                'remark' => 'Exam has been removed from the system'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overall analytics for the teacher (across all classes)
     */
    public function getTeacherAnalytics(Request $request)
    {
        try {
            $teacher = $this->teacherRepository->findByUserId($request->user()->id);

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Teacher record not found',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding teacher record'
                ], 404);
            }

            $analytics = $this->teacherRepository->getTeacherAnalytics($teacher->id);

            return response()->json([
                'data' => $analytics,
                'message' => 'Teacher analytics retrieved successfully',
                'success' => true,
                'remark' => 'Comprehensive analytics across all classes'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve teacher analytics',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
