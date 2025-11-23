<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\StudentRepositoryInterface;

class StudentProgressController extends Controller
{
    protected $studentRepository;

    public function __construct(StudentRepositoryInterface $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function getAverageScore(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $student = $this->studentRepository->findByUserId($request->user_id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found for the given user ID',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding student record'
                ], 404);
            }

            $data = $this->studentRepository->getAverageScore($student->id);

            return response()->json([
                'data' => $data,
                'message' => 'Average score calculated successfully',
                'success' => true,
                'remark' => 'Computed average of highest scores per lesson exercise'
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
                'message' => 'Failed to calculate average score',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getOverallProgress(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]); 

            $student = $this->studentRepository->findByUserId($request->user_id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the given user ID'
                ], 404);
            }

            $data = $this->studentRepository->getOverallProgress($student->id);

            return response()->json([
                'data' => $data,
                'message' => 'Student progress retrieved successfully',
                'success' => true,
                'remark' => 'Calculated overall student progress across all lessons'
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
                'message' => 'Failed to retrieve student progress',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getTopicsProgress(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $student = $this->studentRepository->findByUserId($request->user_id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the given user ID'
                ], 404);
            }

            $progressData = $this->studentRepository->getTopicsProgress($student->id);

            return response()->json([
                'data' => $progressData,
                'message' => 'Topics progress retrieved successfully',
                'success' => true,
                'remark' => 'Calculated student progress for all active topics'
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
                'message' => 'Failed to retrieve topics progress',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getLessonExerciseHistory(Request $request)
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

            $history = $this->studentRepository->getLessonExerciseHistory($student->id);

            return response()->json([
                'data' => $history,
                'message' => 'Lesson exercise history retrieved successfully',
                'success' => true,
                'remark' => 'All lesson exercise progress records for the student'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve lesson exercise history',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getChapterExerciseHistory(Request $request)
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

            $history = $this->studentRepository->getChapterExerciseHistory($student->id);

            return response()->json([
                'data' => $history,
                'message' => 'Chapter exercise history retrieved successfully',
                'success' => true,
                'remark' => 'All chapter exercise progress records for the student'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve chapter exercise history',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getExamHistory(Request $request)
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

            $history = $this->studentRepository->getExamHistory($student->id);

            return response()->json([
                'data' => $history,
                'message' => 'Exam history retrieved successfully',
                'success' => true,
                'remark' => 'All exam progress records for the student'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exam history',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
