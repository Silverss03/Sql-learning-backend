<?php

use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// Registration
Route::post('/register', function (Request $request) {
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
        'message' => 'Registration successful',
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer'
    ], 201);
});

// Login
Route::post('/login', function(Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer'
    ]);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->tokens()->delete();
        
        return response()->json(['message' => 'Logout successful']);
    });
    
    //get topics
    Route::get('/topics', function() {
        $topic = Topic::where('is_active', true)->orderBy('order_index')->get();
    
        return response()->json([
            'message' => 'Topics retrieved successfully',
            'topics' => $topic
        ]);
    });
    
    //get lessons by topic
    Route::get('/topics/{topic}/lessons', function(Topic $topic) {
        if(!$topic->is_active) {
            return response()->json([
                'message' => 'Topic not found or inactive'
            ], 404);
        }
    
        $lessons = $topic->lessons()->where('is_active', true)->orderBy('order_index')->get();
    
        return response()->json([
            'message' => 'Lessons retrieved successfully',
            'lessons' => $lessons
        ]);
    });
    
    //get questions by lesson
    Route::get('/lessons/{lesson}/questions', function($lessonId) {
        $lesson = Lesson::where('id', $lessonId)
            ->where('is_active', true)
            ->first();

        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found or inactive'
            ], 404);    
        }

        $questions = $lesson->question()->where('is_active', true)->orderBy('order_index')->get();

        return response()->json([
            'message' => 'Questions retrieved successfully',
            'questions' => $questions
        ]);
    });

    //get multichoice questions
    Route::get('/lessons/{lesson}/multichoice-questions', function($lessonId) {
        $lesson = Lesson::where('id', $lessonId)
            ->where('is_active', true)
            ->first();
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found or inactive'
            ], 404);    
        }

        $questions = $lesson->questions()
                            ->where('is_active', true)
                            ->where('question_type', 'multiple_choice')
                            ->orderBy('order_index'
                            )->get();

        return response()->json([
            'message' => 'Multiple choice questions retrieved successfully',
            'questions' => $questions
        ]);
    });

    //get all SQL questions
    Route::get('/lessons/{lesson}/question/sql', function($lessonId){
        $lesson = Lesson::where('id', $lessonId)
                        ->where('is_active', true)
                        ->first();  
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found or inactive'
            ], 404);    
        }

        $questions = $lesson->questions()
                            ->where('is_active', true)
                            ->where('question_type', 'sql')
                            ->orderBy('order_index')
                            ->get();

        return response()->json([
            'message' => 'SQL questions retrieved successfully',
            'questions' => $questions
        ]);
    }); 
});
