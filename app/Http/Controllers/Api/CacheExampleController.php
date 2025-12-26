<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

/**
 * Example controller showing Redis/Cache usage in the SQL Learning Platform
 */
class CacheExampleController extends Controller
{
    /**
     * Example 1: Cache user data with automatic invalidation
     */
    public function getUserWithCache($userId)
    {
        try {
            // Cache user data for 1 hour
            $user = Cache::remember("user:profile:{$userId}", 3600, function () use ($userId) {
                return User::with(['admin', 'teacher', 'student'])->find($userId);
            });

            if (!$user) {
                return response()->json([
                    'data' => null,
                    'message' => 'User not found',
                    'success' => false
                ], 404);
            }

            return response()->json([
                'data' => $user,
                'message' => 'User data retrieved successfully',
                'success' => true,
                'cached' => Cache::has("user:profile:{$userId}")
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve user',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 2: Update user and clear cache
     */
    public function updateUserAndClearCache(Request $request, $userId)
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'data' => null,
                    'message' => 'User not found',
                    'success' => false
                ], 404);
            }

            $user->update($request->all());

            // Clear cached user data
            Cache::forget("user:profile:{$userId}");
            
            // Also clear related caches
            Cache::tags(['users'])->flush();

            return response()->json([
                'data' => $user,
                'message' => 'User updated successfully',
                'success' => true,
                'remark' => 'Cache cleared for user'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to update user',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 3: Cache statistics dashboard
     */
    public function getDashboardStats()
    {
        try {
            // Cache dashboard stats for 5 minutes
            $stats = Cache::remember('dashboard:stats', 300, function () {
                return [
                    'total_users' => User::count(),
                    'active_users' => User::where('is_active', true)->count(),
                    'total_students' => User::where('role', 'student')->count(),
                    'total_teachers' => User::where('role', 'teacher')->count(),
                    'generated_at' => now()->toDateTimeString()
                ];
            });

            return response()->json([
                'data' => $stats,
                'message' => 'Dashboard stats retrieved successfully',
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve stats',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 4: Rate limiting with Redis
     */
    public function limitedAction(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $key = "action:limit:{$userId}";

            // Allow 10 requests per minute
            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);
                
                return response()->json([
                    'data' => null,
                    'message' => "Too many requests. Please try again in {$seconds} seconds.",
                    'success' => false,
                    'retry_after' => $seconds
                ], 429);
            }

            RateLimiter::hit($key, 60);

            // Perform your action here
            $remainingAttempts = 10 - RateLimiter::attempts($key);

            return response()->json([
                'data' => [
                    'action' => 'completed',
                    'remaining_attempts' => $remainingAttempts
                ],
                'message' => 'Action completed successfully',
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Action failed',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 5: Cache with tags (requires Redis or Memcached)
     */
    public function getCachedExams($classId)
    {
        try {
            // Cache with tags for easier invalidation
            $exams = Cache::tags(['exams', "class:{$classId}"])->remember(
                "exams:class:{$classId}",
                600,
                function () use ($classId) {
                    return \App\Models\Exam::where('class_id', $classId)
                        ->with('questions')
                        ->get();
                }
            );

            return response()->json([
                'data' => $exams,
                'message' => 'Exams retrieved successfully',
                'success' => true
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
     * Example 6: Clear all exam caches
     */
    public function clearExamCaches()
    {
        try {
            // Clear all caches with 'exams' tag
            Cache::tags(['exams'])->flush();

            return response()->json([
                'data' => null,
                'message' => 'All exam caches cleared successfully',
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to clear caches',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 7: Session data with Redis
     */
    public function sessionExample(Request $request)
    {
        // Store in session (automatically in Redis)
        session(['last_action' => now()]);
        session(['preferences' => $request->get('preferences')]);

        // Retrieve from session
        $lastAction = session('last_action');

        return response()->json([
            'data' => [
                'last_action' => $lastAction,
                'session_id' => session()->getId()
            ],
            'message' => 'Session data managed successfully',
            'success' => true
        ]);
    }

    /**
     * Example 8: Increment counters (useful for analytics)
     */
    public function incrementPageView($pageId)
    {
        try {
            // Increment counter in Redis
            $views = Cache::increment("page:views:{$pageId}");

            // Optionally store in database every 100 views
            if ($views % 100 === 0) {
                // Store to database
                DB::table('page_analytics')->updateOrInsert(
                    ['page_id' => $pageId],
                    ['views' => $views, 'updated_at' => now()]
                );
            }

            return response()->json([
                'data' => ['views' => $views],
                'message' => 'Page view recorded',
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to record view',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 9: Lock mechanism for concurrent requests
     */
    public function performCriticalAction(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $lock = Cache::lock("critical:action:{$userId}", 10);

            if ($lock->get()) {
                try {
                    // Perform critical action that shouldn't run concurrently
                    sleep(2); // Simulate work
                    
                    return response()->json([
                        'data' => ['status' => 'completed'],
                        'message' => 'Critical action completed',
                        'success' => true
                    ]);
                } finally {
                    $lock->release();
                }
            }

            return response()->json([
                'data' => null,
                'message' => 'Another action is in progress. Please wait.',
                'success' => false
            ], 409);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Action failed',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 10: Check Redis connection status
     */
    public function checkRedisConnection()
    {
        try {
            // Ping Redis
            $pong = Redis::ping();
            
            // Get Redis info
            $info = Redis::info();

            return response()->json([
                'data' => [
                    'status' => 'connected',
                    'ping' => $pong,
                    'version' => $info['redis_version'] ?? 'unknown',
                    'used_memory' => $info['used_memory_human'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 'unknown'
                ],
                'message' => 'Redis is connected and working',
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Redis connection failed',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example 11: Store and retrieve complex data structures
     */
    public function storeUserActivity(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $activity = [
                'action' => $request->action,
                'resource' => $request->resource,
                'timestamp' => now()->toDateTimeString(),
                'ip' => $request->ip()
            ];

            // Store in a list (Redis LIST)
            Redis::lpush("user:activity:{$userId}", json_encode($activity));
            
            // Keep only last 100 activities
            Redis::ltrim("user:activity:{$userId}", 0, 99);

            // Get recent activities
            $activities = Redis::lrange("user:activity:{$userId}", 0, 9);
            $activities = array_map('json_decode', $activities);

            return response()->json([
                'data' => [
                    'activity_recorded' => $activity,
                    'recent_activities' => $activities
                ],
                'message' => 'Activity recorded successfully',
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to record activity',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
