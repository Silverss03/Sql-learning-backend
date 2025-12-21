<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\LessonExerciseRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Models\Admin;
use App\Models\Teacher;
use Illuminate\Validation\ValidationException;

class LessonExerciseController extends Controller
{
    protected $lessonExerciseRepository;
    protected $studentRepository;

    public function __construct(
        LessonExerciseRepositoryInterface $lessonExerciseRepository,
        StudentRepositoryInterface $studentRepository
    ) {
        $this->lessonExerciseRepository = $lessonExerciseRepository;
        $this->studentRepository = $studentRepository;
    }

    // ==========================================
    // PUBLIC STUDENT OPERATIONS
    // ==========================================

    /**
     * Get all lesson exercises
     * GET /api/lesson-exercises
     */
    public function index(Request $request)
    {
        try {
            $exercises = $this->lessonExerciseRepository->getAll();

            return response()->json([
                'data' => $exercises,
                'message' => 'Lesson exercises retrieved successfully',
                'success' => true,
                'remark' => 'All lesson exercises with question counts'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve lesson exercises',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific lesson exercise with questions
     * GET /api/lesson-exercises/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            // $student = $this->studentRepository->findByUserId($request->user()->id);

            // if (!$student) {
            //     return response()->json([
            //         'data' => null,
            //         'message' => 'Student not found',
            //         'success' => false,
            //         'remark' => 'No student record for the authenticated user'
            //     ], 404);
            // }

            $exercise = $this->lessonExerciseRepository->getExerciseWithQuestions($id);

            if (!$exercise) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise not found',
                    'success' => false,
                    'remark' => 'The requested lesson exercise does not exist'
                ], 404);
            }

            if (!$exercise['is_active']) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise is not active',
                    'success' => false,
                    'remark' => 'The lesson exercise is currently inactive'
                ], 403);
            }

            return response()->json([
                'data' => $exercise,
                'message' => 'Lesson exercise retrieved successfully',
                'success' => true,
                'remark' => 'Lesson exercise with active questions'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve lesson exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lesson exercise by lesson ID
     * GET /api/lessons/{lessonId}/exercise
     */
    public function getByLesson(Request $request, $lessonId)
    {
        try {
            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $exercise = $this->lessonExerciseRepository->getByLesson($lessonId);

            if (!$exercise) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise not found',
                    'success' => false,
                    'remark' => 'No exercise found for this lesson'
                ], 404);
            }

            if (!$exercise->is_active) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise is not active',
                    'success' => false,
                    'remark' => 'The lesson exercise is currently inactive'
                ], 403);
            }

            return response()->json([
                'data' => $exercise,
                'message' => 'Lesson exercise retrieved successfully',
                'success' => true,
                'remark' => 'Lesson exercise for the specified lesson'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve lesson exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit lesson exercise
     * POST /api/lesson-exercises/submit
     */
    public function submit(Request $request)
    {
        try {
            $request->validate([
                'lesson_exercise_id' => 'required|exists:lesson_exercises,id',
                'score' => 'required|numeric|min:0|max:100'
            ]);

            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            // Check if exercise exists and is active
            $exercise = $this->lessonExerciseRepository->findById($request->lesson_exercise_id);
            
            if (!$exercise) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise not found',
                    'success' => false,
                    'remark' => 'The requested lesson exercise does not exist'
                ], 404);
            }

            if (!$exercise->is_active) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise is not active',
                    'success' => false,
                    'remark' => 'Cannot submit to an inactive exercise'
                ], 403);
            }

            // Check if already completed
            $hasCompleted = $this->lessonExerciseRepository->hasStudentCompleted(
                $student->id,
                $request->lesson_exercise_id
            );

            if ($hasCompleted) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exercise already completed',
                    'success' => false,
                    'remark' => 'Student has already completed this exercise'
                ], 403);
            }

            $progress = $this->lessonExerciseRepository->submitExercise(
                $student->id,
                $request->lesson_exercise_id,
                $request->score
            );

            return response()->json([
                'data' => $progress,
                'message' => 'Exercise submitted successfully',
                'success' => true,
                'remark' => 'Submission recorded and average score updated'
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
                'message' => 'Failed to submit exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's submission for a specific exercise
     * GET /api/lesson-exercises/{id}/submission
     */
    public function getSubmission(Request $request, $id)
    {
        try {
            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $submission = $this->lessonExerciseRepository->getStudentSubmission($student->id, $id);

            if (!$submission) {
                return response()->json([
                    'data' => null,
                    'message' => 'No submission found',
                    'success' => false,
                    'remark' => 'Student has not submitted this exercise yet'
                ], 404);
            }

            return response()->json([
                'data' => $submission,
                'message' => 'Submission retrieved successfully',
                'success' => true,
                'remark' => 'Student submission for the lesson exercise'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve submission',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's submission history for an exercise
     * GET /api/lesson-exercises/{id}/history
     */
    public function getHistory(Request $request, $id)
    {
        try {
            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $history = $this->lessonExerciseRepository->getSubmissionHistory($student->id, $id);

            return response()->json([
                'data' => $history,
                'message' => 'Submission history retrieved successfully',
                'success' => true,
                'remark' => 'All submissions by the student for this exercise'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve submission history',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // ADMIN/TEACHER OPERATIONS (CRUD)
    // ==========================================

    /**
     * Create a new lesson exercise (Admin/Teacher only)
     * POST /api/lesson-exercises
     */
    public function store(Request $request)
    {
        // Verify admin or teacher access
        $admin = Admin::where('user_id', $request->user()->id)->first();
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$admin && !$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins and teachers can create lesson exercises'
            ], 403);
        }

        try {
            $request->validate([
                'lesson_id' => 'required|exists:lessons,id',
                'is_active' => 'nullable|boolean'
            ]);

            $exercise = $this->lessonExerciseRepository->create($request->only([
                'lesson_id',
                'is_active'
            ]));

            return response()->json([
                'data' => $exercise,
                'message' => 'Lesson exercise created successfully',
                'success' => true,
                'remark' => 'New lesson exercise created'
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
                'message' => 'Failed to create lesson exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a lesson exercise (Admin/Teacher only)
     * PUT /api/lesson-exercises/{id}
     */
    public function update(Request $request, $id)
    {
        // Verify admin or teacher access
        $admin = Admin::where('user_id', $request->user()->id)->first();
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$admin && !$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins and teachers can update lesson exercises'
            ], 403);
        }

        try {
            $request->validate([
                'lesson_id' => 'sometimes|exists:lessons,id',
                'is_active' => 'nullable|boolean'
            ]);

            $exercise = $this->lessonExerciseRepository->update($id, $request->only([
                'lesson_id',
                'is_active'
            ]));

            return response()->json([
                'data' => $exercise,
                'message' => 'Lesson exercise updated successfully',
                'success' => true,
                'remark' => 'Lesson exercise information updated'
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
                'message' => 'Failed to update lesson exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lesson exercise (Admin only)
     * DELETE /api/lesson-exercises/{id}
     */
    public function destroy(Request $request, $id)
    {
        // Verify admin access
        $admin = Admin::where('user_id', $request->user()->id)->first();

        if (!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can delete lesson exercises'
            ], 403);
        }

        try {
            $this->lessonExerciseRepository->delete($id);

            return response()->json([
                'data' => null,
                'message' => 'Lesson exercise deleted successfully',
                'success' => true,
                'remark' => 'Lesson exercise and related questions removed'
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

    // ==========================================
    // STATISTICS & ANALYTICS (Admin/Teacher)
    // ==========================================

    /**
     * Get exercise statistics
     * GET /api/lesson-exercises/{id}/statistics
     */
    public function getStatistics(Request $request, $id)
    {
        // Verify admin or teacher access
        $admin = Admin::where('user_id', $request->user()->id)->first();
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$admin && !$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins and teachers can view statistics'
            ], 403);
        }

        try {
            $statistics = $this->lessonExerciseRepository->getExerciseStatistics($id);

            return response()->json([
                'data' => $statistics,
                'message' => 'Exercise statistics retrieved successfully',
                'success' => true,
                'remark' => 'Comprehensive statistics for the lesson exercise'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve statistics',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get completion rate for an exercise
     * GET /api/lesson-exercises/{id}/completion-rate
     */
    public function getCompletionRate(Request $request, $id)
    {
        // Verify admin or teacher access
        $admin = Admin::where('user_id', $request->user()->id)->first();
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$admin && !$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins and teachers can view completion rate'
            ], 403);
        }

        try {
            $completionRate = $this->lessonExerciseRepository->getCompletionRate($id);

            return response()->json([
                'data' => [
                    'exercise_id' => $id,
                    'completion_rate' => $completionRate
                ],
                'message' => 'Completion rate retrieved successfully',
                'success' => true,
                'remark' => 'Percentage of students who completed the exercise'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve completion rate',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
