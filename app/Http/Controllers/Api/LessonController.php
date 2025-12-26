<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\LessonRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Services\RedisCacheService;
use Illuminate\Support\Facades\Cache;

class LessonController extends Controller
{
    protected $lessonRepository;
    protected $studentRepository;
    protected $cache;

    public function __construct(
        LessonRepositoryInterface $lessonRepository,
        StudentRepositoryInterface $studentRepository,
        RedisCacheService $cache
    ) {
        $this->lessonRepository = $lessonRepository;
        $this->studentRepository = $studentRepository;
        $this->cache = $cache;
    }

    /**
     * Get all lessons (optionally filtered by topic)
     */
    public function index(Request $request)
    {
        try {
            $filters = [];
            
            // Add topic filter if provided
            if ($request->has('topic_id')) {
                $filters['topic_id'] = $request->topic_id;
            }

            // Cache lessons list for 1 hour
            $cacheKey = isset($filters['topic_id']) 
                ? "lessons:topic:{$filters['topic_id']}" 
                : 'lessons:all';
            
            $lessons = Cache::remember(
                $cacheKey,
                RedisCacheService::CACHE_LONG,
                function () use ($filters) {
                    return $this->lessonRepository->getAll($filters);
                }
            );

            return response()->json([
                'data' => $lessons,
                'message' => 'Lessons retrieved successfully',
                'success' => true,
                'remark' => 'All active lessons' . (isset($filters['topic_id']) ? ' for the specified topic' : '')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve lessons',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lesson details with exercises and questions
     */
    public function show($id)
    {
        try {
            // Cache lesson details for 1 hour
            $lesson = Cache::remember(
                "lessons:{$id}:details",
                RedisCacheService::CACHE_LONG,
                function () use ($id) {
                    return $this->lessonRepository->getByIdWithDetails($id);
                }
            );

            if (!$lesson) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson not found or inactive',
                    'success' => false,
                    'remark' => 'The requested lesson is not available'
                ], 404);
            }

            return response()->json([
                'data' => $lesson,
                'message' => 'Lesson details retrieved successfully',
                'success' => true,
                'remark' => 'Lesson with topic, exercises, and questions'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve lesson details',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestions($lessonId)
    {
        try {
            // Cache lesson questions for 1 hour
            $questions = Cache::remember(
                "lessons:{$lessonId}:questions",
                RedisCacheService::CACHE_LONG,
                function () use ($lessonId) {
                    return $this->lessonRepository->getQuestionsByLesson($lessonId);
                }
            );

            if ($questions === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson not found or inactive',
                    'success' => false,
                    'remark' => 'The requested lesson is not available'
                ], 404);    
            }

            return response()->json([
                'data' => $questions,
                'message' => 'Questions retrieved successfully',
                'success' => true,
                'remark' => 'All active questions for the specified lesson'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve questions',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getExercise($lessonId)
    {
        try {
            // Cache lesson exercise for 1 hour
            $data = Cache::remember(
                "lessons:{$lessonId}:exercise",
                RedisCacheService::CACHE_LONG,
                function () use ($lessonId) {
                    return $this->lessonRepository->getExerciseByLesson($lessonId);
                }
            );

            if ($data === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise not found or inactive',
                    'success' => false,
                    'remark' => 'The requested lesson exercise is not available'
                ], 404);
            }

            return response()->json([
                'data' => $data,
                'message' => 'Lesson exercise and questions retrieved successfully',
                'success' => true,
                'remark' => 'Active lesson exercise and its questions for the specified lesson'
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

    public function submitExercise(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'lesson_exercise_id' => 'required|exists:lesson_exercises,id',
                'score' => 'required|numeric|min:0'
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

            $progress = $this->lessonRepository->submitExercise(
                $student->id,
                $request->lesson_exercise_id,
                $request->score
            );

            // Clear related caches after exercise submission
            $this->cache->clearStudentCache($student->id);

            return response()->json([
                'data' => $progress,
                'message' => 'Exercise submitted successfully',
                'success' => true,
                'remark' => 'Submission record created'
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
                'message' => 'Failed to submit exercise result',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
