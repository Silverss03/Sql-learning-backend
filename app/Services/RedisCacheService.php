<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Redis Cache Service for SQL Learning Platform
 * 
 * This service provides reusable caching methods for the application
 */
class RedisCacheService
{
    /**
     * Cache Time Constants (in seconds)
     */
    const CACHE_SHORT = 300;      // 5 minutes
    const CACHE_MEDIUM = 1800;    // 30 minutes
    const CACHE_LONG = 3600;      // 1 hour
    const CACHE_VERY_LONG = 86400; // 24 hours

    /**
     * Get or cache user data
     */
    public function getUserCache($userId, $ttl = self::CACHE_LONG)
    {
        return Cache::remember("user:{$userId}", $ttl, function () use ($userId) {
            return \App\Models\User::with(['admin', 'teacher', 'student'])->find($userId);
        });
    }

    /**
     * Clear user cache
     */
    public function clearUserCache($userId)
    {
        Cache::forget("user:{$userId}");
        Cache::forget("user:profile:{$userId}");
        Cache::forget("user:stats:{$userId}");
        
        Log::info("Cleared cache for user: {$userId}");
    }

    /**
     * Clear student cache (progress, exercises, exams)
     */
    public function clearStudentCache($studentId)
    {
        // Clear all student-related caches
        Cache::forget("students:{$studentId}:exam_history");
        Cache::forget("students:{$studentId}:average_score");
        Cache::forget("students:{$studentId}:overall_progress");
        Cache::forget("students:{$studentId}:topics_progress");
        
        // Clear topic-specific progress caches
        $topics = \App\Models\Topic::all();
        foreach ($topics as $topic) {
            Cache::forget("topics:{$topic->id}:progress:student:{$studentId}");
            Cache::forget("topics:{$topic->id}:chapter_exercises:student:{$studentId}");
        }
        
        Log::info("Cleared cache for student: {$studentId}");
    }

    /**
     * Get or cache class data
     */
    public function getClassCache($classId, $ttl = self::CACHE_MEDIUM)
    {
        return Cache::remember("class:{$classId}", $ttl, function () use ($classId) {
            return \App\Models\ClassModel::with(['students', 'teacher'])->find($classId);
        });
    }

    /**
     * Clear class cache
     */
    public function clearClassCache($classId)
    {
        Cache::forget("class:{$classId}");
        Cache::forget("class:students:{$classId}");
        Cache::forget("class:exams:{$classId}");
        Cache::tags(['classes', "class:{$classId}"])->flush();
        
        Log::info("Cleared cache for class: {$classId}");
    }

    /**
     * Get or cache exam data
     */
    public function getExamCache($examId, $ttl = self::CACHE_MEDIUM)
    {
        return Cache::remember("exam:{$examId}", $ttl, function () use ($examId) {
            return \App\Models\Exam::with(['questions', 'classModel', 'topic'])->find($examId);
        });
    }

    /**
     * Clear exam cache
     */
    public function clearExamCache($examId = null)
    {
        if ($examId) {
            Cache::forget("exam:{$examId}");
            Cache::forget("exam:questions:{$examId}");
            Cache::forget("exam:results:{$examId}");
        }
        
        Cache::tags(['exams'])->flush();
        
        Log::info("Cleared exam cache" . ($examId ? " for exam: {$examId}" : " (all exams)"));
    }

    /**
     * Get or cache active exams
     */
    public function getActiveExamsCache($classId = null, $ttl = self::CACHE_SHORT)
    {
        $key = $classId ? "exams:active:class:{$classId}" : "exams:active";
        
        return Cache::remember($key, $ttl, function () use ($classId) {
            $query = \App\Models\Exam::where('is_active', true);
            
            if ($classId) {
                $query->where('class_id', $classId);
            }
            
            return $query->with(['questions', 'classModel', 'topic'])->get();
        });
    }

    /**
     * Get or cache user statistics
     */
    public function getUserStatsCache($userId, $ttl = self::CACHE_MEDIUM)
    {
        return Cache::remember("user:stats:{$userId}", $ttl, function () use ($userId) {
            return [
                'avg_score' => \App\Models\Student::where('user_id', $userId)->value('avg_score'),
            ];
        });
    }


    /**
     * Increment view counter
     */
    public function incrementViewCount($resourceType, $resourceId)
    {
        $key = "views:{$resourceType}:{$resourceId}";
        return Cache::increment($key);
    }

    /**
     * Get view count
     */
    public function getViewCount($resourceType, $resourceId)
    {
        $key = "views:{$resourceType}:{$resourceId}";
        return Cache::get($key, 0);
    }

    /**
     * Store temporary data (e.g., verification codes)
     */
    public function storeTempData($key, $value, $ttl = 900) // 15 minutes default
    {
        Cache::put("temp:{$key}", $value, $ttl);
    }

    /**
     * Get temporary data
     */
    public function getTempData($key)
    {
        return Cache::get("temp:{$key}");
    }

    /**
     * Delete temporary data
     */
    public function deleteTempData($key)
    {
        Cache::forget("temp:{$key}");
    }

    /**
     * Cache search results
     */
    public function cacheSearchResults($query, $results, $ttl = self::CACHE_SHORT)
    {
        $key = "search:" . md5($query);
        Cache::put($key, $results, $ttl);
    }

    /**
     * Get cached search results
     */
    public function getCachedSearchResults($query)
    {
        $key = "search:" . md5($query);
        return Cache::get($key);
    }

    /**
     * Clear all application caches
     */
    public function clearAllCache()
    {
        Cache::flush();
        Log::info("All application cache cleared");
    }

    /**
     * Clear caches by tag
     */
    public function clearCacheByTag($tag)
    {
        Cache::tags([$tag])->flush();
        Log::info("Cache cleared for tag: {$tag}");
    }

    /**
     * Clear multiple caches by tags
     */
    public function clearCacheByTags(array $tags)
    {
        Cache::tags($tags)->flush();
        Log::info("Cache cleared for tags: " . implode(', ', $tags));
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            // Add more stats as needed
        ];
    }

    /**
     * Check if cache key exists
     */
    public function has($key)
    {
        return Cache::has($key);
    }

    /**
     * Remember forever (until manually cleared)
     */
    public function rememberForever($key, $callback)
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Get data with cache lock to prevent stampede (for high-traffic endpoints)
     * 
     * This method prevents cache stampede by using a distributed lock.
     * When cache misses, only one request rebuilds the cache while others wait.
     * 
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Function to generate data if cache misses
     * @param int $lockTimeout Lock timeout in seconds (default: 10)
     * @return mixed Cached or freshly generated data
     */
    public function rememberWithLock($key, $ttl, $callback, $lockTimeout = 10)
    {
        // Try to get from cache first (fast path)
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        // Cache miss - acquire lock to rebuild
        $lockKey = "lock:{$key}";
        $lock = Cache::lock($lockKey, $lockTimeout);
        
        try {
            // Try to acquire lock
            if ($lock->get()) {
                // Got lock - rebuild cache
                try {
                    // Double-check cache (might have been built while waiting)
                    $cached = Cache::get($key);
                    if ($cached !== null) {
                        Log::info("Cache hit after lock acquired: {$key}");
                        return $cached;
                    }
                    
                    // Build cache
                    Log::info("Rebuilding cache with lock: {$key}");
                    $data = $callback();
                    Cache::put($key, $data, $ttl);
                    
                    return $data;
                } finally {
                    // Always release lock
                    $lock->release();
                }
            } else {
                // Failed to get lock - another request is rebuilding
                Log::info("Lock not acquired, waiting for cache: {$key}");
                
                // Wait briefly for the other request to finish
                usleep(100000); // Wait 100ms
                
                // Try cache again (should be ready now)
                $cached = Cache::get($key);
                if ($cached !== null) {
                    return $cached;
                }
                
                // Still no cache? Fallback to direct DB query
                Log::warning("Cache still empty after lock wait, executing callback: {$key}");
                return $callback();
            }
        } catch (\Exception $e) {
            // If locking fails, fallback to direct execution
            Log::error("Cache lock error for {$key}: " . $e->getMessage());
            return $callback();
        }
    }
}
