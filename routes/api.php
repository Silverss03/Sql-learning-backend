<?php

use App\Http\Controllers\SqlExecutionController;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\MultipleChoiceQuestion;
use App\Models\SQLQuestion;
use App\Models\Question;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// Registration
Route::post('/register', function (Request $request) {
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,teacher,student'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ],
            'message' => 'Registration successful',
            'success' => true,
            'remark' => 'User registered and authenticated successfully'
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
            'message' => 'Registration failed',
            'success' => false,
            'remark' => $e->getMessage()
        ], 500);
    }
});

// Login
Route::post('/login', function(Request $request) {
    try {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'data' => null,
                'message' => 'Invalid credentials',
                'success' => false,
                'remark' => 'Email or password is incorrect'
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ],
            'message' => 'Login successful',
            'success' => true,
            'remark' => 'User authenticated successfully'
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
            'message' => 'Login failed',
            'success' => false,
            'remark' => $e->getMessage()
        ], 500);
    }
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'data' => $request->user(),
            'message' => 'User data retrieved successfully',
            'success' => true,
            'remark' => 'Current authenticated user information'
        ]);
    });

    Route::post('/logout', function (Request $request) {
        try {
            $request->user()->tokens()->delete();
            
            return response()->json([
                'data' => null,
                'message' => 'Logout successful',
                'success' => true,
                'remark' => 'All user tokens have been revoked'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Logout failed',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });
    
    // Get topics
    Route::get('/topics', function() {
        try {
            $topics = Topic::where('is_active', true)->orderBy('order_index')->get();
        
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
    });
    
    // Get lessons by topic
    Route::get('/topics/{topic}/lessons', function(Topic $topic) {
        try {
            if(!$topic->is_active) {
                return response()->json([
                    'data' => null,
                    'message' => 'Topic not found or inactive',
                    'success' => false,
                    'remark' => 'The requested topic is not available'
                ], 404);
            }

            $lessons = $topic->lessons()->where('is_active', true)->orderBy('order_index')->get();

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
    });
    
    // Get questions by lesson
    Route::get('/lessons/{lesson}/questions', function($lessonId) {
        try {
            $lesson = Lesson::where('id', $lessonId)
                ->where('is_active', true)
                ->first();

            if (!$lesson) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson not found or inactive',
                    'success' => false,
                    'remark' => 'The requested lesson is not available'
                ], 404);    
            }

            $questions = $lesson->questions()->where('is_active', true)->orderBy('order_index')->get();

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
    });

    // Get multiple choice questions
    Route::get('/lessons/{lesson}/multichoice-questions', function($lessonId) {
        $questions = Question::where('lesson_id', $lessonId)
            ->where('is_active', true)
            ->where('question_type', 'multiple_choice')
            ->orderBy('order_index')
            ->with('multipleChoice')
            ->get();

        $multipleChoiceQuestions = $questions->pluck('multipleChoice')->filter();

        return response()->json([
            'data' => $multipleChoiceQuestions,
            'message' => 'Multiple choice questions retrieved successfully',
            'success' => true,
            'remark' => 'All active multiple choice questions for the specified lesson'
        ]);
    });

    // Get SQL questions
    Route::get('/lessons/{lesson}/questions/interactive-sql', function ($lessonId) {
    try {
        // Fetch all questions of type 'interactive_sql' for the given lesson
        $questions = Question::where('lesson_id', $lessonId)
            ->where('is_active', true)
            ->where('question_type', 'sql')
            ->orderBy('order_index')
            ->with('interactiveSqlQuestion') // Eager load the related InteractiveSqlQuestion
            ->get();

        // Extract the interactive SQL question data
        $interactiveSqlQuestions = $questions->pluck('interactiveSqlQuestion')->filter();

        return response()->json([
            'data' => $interactiveSqlQuestions,
            'message' => 'Interactive SQL questions retrieved successfully',
            'success' => true,
            'remark' => 'All active interactive SQL questions for the specified lesson',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'data' => null,
            'message' => 'Failed to retrieve interactive SQL questions',
            'success' => false,
            'remark' => $e->getMessage(),
        ], 500);
    }
});
});

Route::post('/execute-sql', [SqlExecutionController::class, 'executeQuery']);
Route::get('/questions/{id}', [SqlExecutionController::class, 'getQuestion']);