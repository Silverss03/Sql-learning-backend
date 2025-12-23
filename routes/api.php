<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\StudentProgressController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ChapterExerciseController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\LessonExerciseController;
use App\Http\Controllers\Api\ExamAuditLogController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// ============================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================

// Authentication routes
// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ============================================
// PROTECTED ROUTES (Authentication Required)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    
    // ============================================
    // USER & AUTH ROUTES
    // ============================================
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    
    // ============================================
    // TOPIC ROUTES
    // ============================================
    Route::prefix('topics')->group(function () {
        Route::get('/', [TopicController::class, 'index']);
        Route::get('/{topic}/lessons', [TopicController::class, 'getLessons']);
        Route::get('/{topic}/chapter-exercises', [TopicController::class, 'getChapterExercises']);
        Route::get('/{topic}/progress', [TopicController::class, 'getProgress']);
    });

    // ============================================
    // LESSON ROUTES
    // ============================================
    Route::prefix('lessons')->group(function () {
        // Get all lessons (optionally filtered by topic_id)
        Route::get('/', [LessonController::class, 'index']);
        
        // Get lesson details with exercises and questions
        Route::get('/{lesson}', [LessonController::class, 'show']);
        
        // Get questions for a lesson (legacy)
        Route::get('/{lesson}/questions', [LessonController::class, 'getQuestions']);
        
        // Get exercise for a lesson (legacy)
        Route::get('/{lesson}/exercise', [LessonExerciseController::class, 'getByLesson']); // Updated to use LessonExerciseController
    });

    // ============================================
    // LESSON EXERCISE ROUTES
    // ============================================
    Route::prefix('lesson-exercises')->group(function () {
        // Student operations (protected by auth)
        Route::get('/', [LessonExerciseController::class, 'index']);                    // Get all exercises
        Route::get('/{id}', [LessonExerciseController::class, 'show']);                 // Get specific exercise
        Route::get('/{id}/submission', [LessonExerciseController::class, 'getSubmission']); // Get student submission
        Route::get('/{id}/history', [LessonExerciseController::class, 'getHistory']);   // Get submission history
        Route::post('/submit', [LessonExerciseController::class, 'submit']);            // Submit exercise
        
        // Admin/Teacher operations
        Route::post('/', [LessonExerciseController::class, 'store']);                   // Create exercise
        Route::put('/{id}', [LessonExerciseController::class, 'update']);               // Update exercise
        Route::delete('/{id}', [LessonExerciseController::class, 'destroy']);           // Delete exercise
        Route::post('/{id}/activate', [LessonExerciseController::class, 'activate']);   // Activate exercise
        Route::post('/{id}/deactivate', [LessonExerciseController::class, 'deactivate']); // Deactivate exercise
        
        // Statistics (Admin/Teacher only)
        Route::get('/{id}/statistics', [LessonExerciseController::class, 'getStatistics']);     // Get statistics
        Route::get('/{id}/completion-rate', [LessonExerciseController::class, 'getCompletionRate']); // Get completion rate
    });
    
    // Legacy route for backward compatibility (will be deprecated)
    Route::post('/exercise/submit', [LessonController::class, 'submitExercise']);

    // ============================================
    // STUDENT PROGRESS ROUTES
    // ============================================
    Route::prefix('students')->group(function () {
        Route::get('/average-score', [StudentProgressController::class, 'getAverageScore']);
        Route::get('/progress', [StudentProgressController::class, 'getOverallProgress']);
        Route::get('/topics-progress', [StudentProgressController::class, 'getTopicsProgress']);
        Route::get('/lesson-exercise-history', [StudentProgressController::class, 'getLessonExerciseHistory']);
        Route::get('/chapter-exercise-history', [StudentProgressController::class, 'getChapterExerciseHistory']);
        Route::get('/exam-history', [StudentProgressController::class, 'getExamHistory']);
    });

    // ============================================
    // CHAPTER EXERCISE ROUTES
    // ============================================
    Route::prefix('chapter-exercises')->group(function () {
        Route::get('/', [ChapterExerciseController::class, 'index']);
        Route::post('/', [ChapterExerciseController::class, 'store']);
        Route::get('/{chapterExercise}', [ChapterExerciseController::class, 'show']);
        Route::put('/{chapterExercise}', [ChapterExerciseController::class, 'update']);
        Route::delete('/{chapterExercise}', [ChapterExerciseController::class, 'destroy']);
    });
    
    Route::post('/chapter-exercise/submit', [ChapterExerciseController::class, 'submit']);

    // ============================================
    // QUESTION/EXERCISE ROUTES (Create exercises with questions)
    // ============================================
    Route::prefix('questions')->group(function () {
        Route::post('/', [QuestionController::class, 'store']);
        Route::get('/{question}', [QuestionController::class, 'show']);
        Route::put('/{question}', [QuestionController::class, 'update']);
        Route::delete('/{question}', [QuestionController::class, 'destroy']);
    });

    // ============================================
    // EXAM ROUTES
    // ============================================
    Route::prefix('exams')->group(function () {
        Route::get('/', [ExamController::class, 'index']);
        Route::get('/history', [ExamController::class, 'getExamHistory']);
        Route::get('/{exam}', [ExamController::class, 'show']);
        Route::put('/{exam}', [ExamController::class, 'update']);
        Route::delete('/{exam}', [ExamController::class, 'destroy']);
        Route::post('/start', [ExamController::class, 'start']);
        Route::post('/submit', [ExamController::class, 'submit']);
        Route::put('/{exam}/start', [ExamController::class, 'activateExam']);
    });

    // ============================================
    // EXAM AUDIT LOG ROUTES
    // ============================================
    Route::prefix('exam-audit-logs')->group(function () {
        // Student: Log tab switch event
        Route::post('/', [ExamAuditLogController::class, 'store']);
        
        // Admin/Teacher: View logs
        Route::get('/exam/{examId}', [ExamAuditLogController::class, 'getByExam']);
        Route::get('/student/{studentId}', [ExamAuditLogController::class, 'getByStudent']);
        Route::get('/session/{sessionToken}', [ExamAuditLogController::class, 'getBySession']);
        Route::get('/session/{sessionToken}/count', [ExamAuditLogController::class, 'getTabSwitchCount']);
    });
    
    // Legacy audit log route (for backward compatibility - will be deprecated)
    Route::post('/audit-logs', [ExamController::class, 'logAudit']);

    // ============================================
    // DEVICE TOKEN & NOTIFICATION ROUTES
    // ============================================
    Route::prefix('users/device-token')->group(function () {
        // Register/Update device token
        Route::post('/', [DeviceTokenController::class, 'store']);
        
        // Get all device tokens for user
        Route::get('/', [DeviceTokenController::class, 'index']);
        
        // Delete device token by ID
        Route::delete('/{id}', [DeviceTokenController::class, 'destroy']);
        
        // Delete device token by token string (for logout)
        Route::delete('/by-token', [DeviceTokenController::class, 'deleteByToken']);
        
        // Test notification (for debugging)
        Route::post('/test-notification', [DeviceTokenController::class, 'testNotification']);
    });

    // ============================================
    // NOTIFICATION ROUTES
    // ============================================
    // NOTIFICATION ROUTES
    // ============================================
    Route::prefix('notifications')->group(function () {
        // Get all notifications for authenticated user (with filters and pagination)
        Route::get('/', [NotificationController::class, 'index']);
        
        // Get notification statistics
        Route::get('/statistics', [NotificationController::class, 'statistics']);
        
        // Get unread notification count
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        
        // Get a single notification
        Route::get('/{id}', [NotificationController::class, 'show']);
        
        // Mark notification as read
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        
        // Mark multiple notifications as read
        Route::post('/mark-multiple-as-read', [NotificationController::class, 'markMultipleAsRead']);
        // Mark all notifications as read
        Route::post('/mark-as-read', [NotificationController::class, 'markAllAsRead']);
        
        // Delete a single notification
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        
        // Delete multiple notifications
        Route::delete('/', [NotificationController::class, 'destroyMultiple']);
        
        // Clear all notifications
        Route::post('/clear-all', [NotificationController::class, 'clearAll']);
    });

    // ============================================
    // ADMIN ROUTES
    // ============================================
    Route::prefix('admin')->group(function () {
        
        // Teacher Management
        Route::post('/teachers', [AdminController::class, 'createTeacher']);
        Route::post('/teachers/batch', [AdminController::class, 'batchCreateTeachers']);
        Route::get('/teachers', [AdminController::class, 'getTeachers']);
        Route::delete('/teachers/{teacher}', [AdminController::class, 'deleteTeacher']);
        Route::post('/teachers/batch-delete', [AdminController::class, 'batchDeleteTeachers']);
        
        // Class Management
        Route::post('/classes', [AdminController::class, 'createClass']);
        Route::post('/classes/bulk', [AdminController::class, 'bulkCreateClasses']);
        Route::get('/classes', [AdminController::class, 'getClasses']);
        Route::get('/classes/{classModel}', [AdminController::class, 'getClass']);
        Route::put('/classes/{classModel}', [AdminController::class, 'updateClass']);
        Route::delete('/classes/{classModel}', [AdminController::class, 'deleteClass']);
        Route::post('/classes/batch-delete', [AdminController::class, 'batchDeleteClasses']);
        
        // Student Management
        Route::post('/students', [AdminController::class, 'createStudent']);
        Route::post('/students/batch', [AdminController::class, 'batchCreateStudents']);
        Route::get('/students', [AdminController::class, 'getStudents']);
        Route::delete('/students/{student}', [AdminController::class, 'deleteStudent']);
        Route::post('/students/batch-delete', [AdminController::class, 'batchDeleteStudents']);
        Route::put('/students/{student}/remove-from-class', [AdminController::class, 'removeStudentFromClass']);
        Route::post('/students/batch-remove-from-classes', [AdminController::class, 'batchRemoveStudentsFromClasses']);
        
        // Lesson Management
        Route::post('/lessons', [AdminController::class, 'createLesson']);
        Route::put('/lessons/{lesson}', [AdminController::class, 'updateLesson']);
        Route::delete('/lessons/{lesson}', [AdminController::class, 'deleteLesson']);
        
        // Lesson Exercise Management
        Route::post('/lesson-exercises', [AdminController::class, 'createLessonExercise']);
        Route::put('/lesson-exercises/{exercise}', [AdminController::class, 'updateLessonExercise']);
        Route::delete('/lesson-exercises/{exercise}', [AdminController::class, 'deleteLessonExercise']);
    });
});
