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
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ============================================
// PROTECTED ROUTES (Authentication Required)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    
    // ============================================
    // USER & AUTH ROUTES
    // ============================================
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
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
        Route::get('/{lesson}/questions', [LessonController::class, 'getQuestions']);
        Route::get('/{lesson}/exercise', [LessonController::class, 'getExercise']);
    });
    
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
        Route::get('/{exam}', [ExamController::class, 'show']);
        Route::put('/{exam}', [ExamController::class, 'update']);
        Route::delete('/{exam}', [ExamController::class, 'destroy']);
        Route::post('/start', [ExamController::class, 'start']);
        Route::post('/submit', [ExamController::class, 'submit']);
        Route::put('/{exam}/start', [ExamController::class, 'activateExam']);
    });

    // ============================================
    // AUDIT LOG ROUTES
    // ============================================
    Route::post('/audit-logs', [ExamController::class, 'logAudit']);    // ============================================
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
    });
});
