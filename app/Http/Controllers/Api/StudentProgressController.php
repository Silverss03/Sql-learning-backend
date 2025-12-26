<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Services\RedisCacheService;
use Illuminate\Support\Facades\Cache;

class StudentProgressController extends Controller
{
    protected $studentRepository;
    protected $cache;

    public function __construct(
        StudentRepositoryInterface $studentRepository,
        RedisCacheService $cache
    ) {
        $this->studentRepository = $studentRepository;
        $this->cache = $cache;
    }

    public function getAverageScore(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $userId = $request->user_id;
            
            // Cache average score for 30 minutes
            $data = Cache::remember("student:avg_score:{$userId}", RedisCacheService::CACHE_MEDIUM, function () use ($userId) {
                $student = $this->studentRepository->findByUserId($userId);
                
                if (!$student) {
                    return null;
                }
                
                return $this->studentRepository->getAverageScore($student->id);
            });

            if ($data === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found for the given user ID',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding student record'
                ], 404);
            }

            return response()->json([
                'data' => $data,
                'message' => 'Average score calculated successfully',
                'success' => true,
                'remark' => 'Computed average of highest scores per lesson exercise (cached)'
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

            $userId = $request->user_id;
            
            // Cache overall progress for 15 minutes
            $data = Cache::remember("student:overall_progress:{$userId}", RedisCacheService::CACHE_SHORT, function () use ($userId) {
                $student = $this->studentRepository->findByUserId($userId);
                
                if (!$student) {
                    return null;
                }
                
                return $this->studentRepository->getOverallProgress($student->id);
            });

            if ($data === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the given user ID'
                ], 404);
            }

            return response()->json([
                'data' => $data,
                'message' => 'Student progress retrieved successfully',
                'success' => true,
                'remark' => 'Calculated overall student progress across all lessons (cached)'
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

            $userId = $request->user_id;
            
            // Cache topics progress for 15 minutes
            $progressData = Cache::remember("student:topics_progress:{$userId}", RedisCacheService::CACHE_SHORT, function () use ($userId) {
                $student = $this->studentRepository->findByUserId($userId);
                
                if (!$student) {
                    return null;
                }
                
                return $this->studentRepository->getTopicsProgress($student->id);
            });

            if ($progressData === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the given user ID'
                ], 404);
            }

            return response()->json([
                'data' => $progressData,
                'message' => 'Topics progress retrieved successfully',
                'success' => true,
                'remark' => 'Retrieved progress for all topics (cached)'
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
            $userId = $request->user()->id;
            
            // Cache lesson exercise history for 10 minutes
            $history = Cache::remember("student:lesson_exercise_history:{$userId}", 600, function () use ($userId) {
                $student = $this->studentRepository->findByUserId($userId);
                
                if (!$student) {
                    return null;
                }
                
                return $this->studentRepository->getLessonExerciseHistory($student->id);
            });

            if ($history === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            return response()->json([
                'data' => $history,
                'message' => 'Lesson exercise history retrieved successfully',
                'success' => true,
                'remark' => 'All lesson exercise progress records for the student (cached)'
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
            $userId = $request->user()->id;
            
            // Cache chapter exercise history for 10 minutes
            $history = Cache::remember("student:chapter_exercise_history:{$userId}", 600, function () use ($userId) {
                $student = $this->studentRepository->findByUserId($userId);
                
                if (!$student) {
                    return null;
                }
                
                return $this->studentRepository->getChapterExerciseHistory($student->id);
            });

            if ($history === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            return response()->json([
                'data' => $history,
                'message' => 'Chapter exercise history retrieved successfully',
                'success' => true,
                'remark' => 'All chapter exercise progress records for the student (cached)'
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
            $userId = $request->user()->id;
            
            // Cache exam history for 10 minutes
            $history = Cache::remember("student:exam_history:{$userId}", 600, function () use ($userId) {
                $student = $this->studentRepository->findByUserId($userId);
                
                if (!$student) {
                    return null;
                }
                
                return $this->studentRepository->getExamHistory($student->id);
            });

            if ($history === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            return response()->json([
                'data' => $history,
                'message' => 'Exam history retrieved successfully',
                'success' => true,
                'remark' => 'All exam progress records for the student (cached)'
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
