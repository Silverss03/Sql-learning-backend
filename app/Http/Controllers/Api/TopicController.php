<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Interfaces\TopicRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Services\RedisCacheService;
use Illuminate\Support\Facades\Cache;

class TopicController extends Controller
{
    protected $topicRepository;
    protected $studentRepository;
    protected $cache;

    public function __construct(
        TopicRepositoryInterface $topicRepository,
        StudentRepositoryInterface $studentRepository,
        RedisCacheService $cache
    ) {
        $this->topicRepository = $topicRepository;
        $this->studentRepository = $studentRepository;
        $this->cache = $cache;
    }

    public function index()
    {
        try {
            // Cache all active topics for 1 hour (topics rarely change)
            $topics = Cache::remember(
                'topics:all:active',
                RedisCacheService::CACHE_LONG,
                function () {
                    return $this->topicRepository->getAllActive();
                }
            );
        
            return response()->json([
                'data' => $topics,
                'message' => 'Topics retrieved successfully',
                'success' => true,
                'remark' => 'All active topics ordered by index'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve topics',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getLessons($topicId)
    {
        try {
            // Cache lessons by topic for 1 hour
            $lessons = Cache::remember(
                "topics:{$topicId}:lessons",
                RedisCacheService::CACHE_LONG,
                function () use ($topicId) {
                    return $this->topicRepository->getLessonsByTopic($topicId);
                }
            );

            if ($lessons === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Topic not found or inactive',
                    'success' => false,
                    'remark' => 'The requested topic is not available'
                ], 404);
            }

            return response()->json([
                'data' => $lessons,
                'message' => 'Lessons retrieved successfully',
                'success' => true,
                'remark' => 'All active lessons for the specified topic'
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

    public function getChapterExercises(Request $request, $topicId)
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

            // Cache chapter exercises with student progress for 15 minutes
            $exercises = Cache::remember(
                "topics:{$topicId}:chapter_exercises:student:{$student->id}",
                RedisCacheService::CACHE_SHORT,
                function () use ($topicId, $student) {
                    return $this->topicRepository->getChapterExercisesByTopic($topicId, $student->id);
                }
            );

            return response()->json([
                'data' => $exercises,
                'message' => 'Chapter exercises retrieved successfully',
                'success' => true,
                'remark' => 'All active chapter exercises for the topic, with student progress data'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve chapter exercises',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    public function getProgress(Request $request, $topicId)
    {
        try {
            $student = $this->studentRepository->findByUserId($request->user()->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the authenticated user'
                ], 404);
            }

            // Cache topic progress for 15 minutes
            $progress = Cache::remember(
                "topics:{$topicId}:progress:student:{$student->id}",
                RedisCacheService::CACHE_SHORT,
                function () use ($topicId, $student) {
                    return $this->topicRepository->getTopicProgress($topicId, $student->id);
                }
            );

            if ($progress === null) {
                return response()->json([
                    'data' => null,
                    'message' => 'Topic not found',
                    'success' => false,
                    'remark' => 'The requested topic does not exist'
                ], 404);
            }

            return response()->json([
                'data' => $progress,
                'message' => 'Topic progress retrieved successfully',
                'success' => true,
                'remark' => 'Calculated student progress for the specified topic'
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
                'message' => 'Failed to retrieve topic progress',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
