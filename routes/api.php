<?php

use App\Http\Controllers\SqlExecutionController;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LessonExercise;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\StudentLessonProgress;

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

    Route::get('/lessons/{lesson}/exercise', function($lessonId) {
        try {
            $lessonExercise = LessonExercise::where('lesson_id', $lessonId)
                ->where('is_active', true)
                ->first();

            if (!$lessonExercise) {
                return response()->json([
                    'data' => null,
                    'message' => 'Lesson exercise not found or inactive',
                    'success' => false,
                    'remark' => 'The requested lesson exercise is not available'
                ], 404);
            }

            // Retrieve questions related to the lesson exercise
            $questions = $lessonExercise->questions()->where('is_active', true)->get();

            // Separate questions into multiple-choice and SQL questions
            $multipleChoiceQuestions = $questions->map(function ($question) {
                return $question->multipleChoice;
            })->filter();

            $sqlQuestions = $questions->map(function ($question) {
                return $question->interactiveSqlQuestion;
            })->filter();

            return response()->json([
                'data' => [
                    'lessonExercise' => $lessonExercise,
                    'questions' => [
                        'multipleChoice' => $multipleChoiceQuestions,
                        'sqlQuestions' => $sqlQuestions
                    ]
                ],
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
    });

    Route::post('/exercise/submit', function(Request $request) {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'lesson_exercise_id' => 'required|exists:lesson_exercises,id',
                'score' => 'required|numeric|min:0'
            ]);

            $student = Student::where('user_id', $request->user_id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found for the given user ID',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding student record'
                ], 404);
            }

            $submission = Submission::create([
                'student_id' => $student->id,
                'lesson_exercise_id' => $request->lesson_exercise_id,
                'score' => $request->score,
            ]);

            $lessonExercise = LessonExercise::find($request->lesson_exercise_id);
            $lessonId = $lessonExercise->lesson_id;

            StudentLessonProgress::updateOrCreate(
                ['student_id' => $student->id, 'lesson_id' => $lessonId],
                ['finished_at' => now()]
            );

            return response()->json([
                'data' => $submission,
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
    });

    Route::get('/students/average-score', function(Request $request) {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $student = Student::where('user_id', $request->user_id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found for the given user ID',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding student record'
                ], 404);
            }

            $highestScore = DB::table('submissions')
                    ->selectRaw('lesson_exercise_id, MAX(score) as max_score')
                    ->where('student_id', $student->id)
                    ->groupBy('lesson_exercise_id')
                    ->get();
            
            $averageScore = $highestScore->avg('max_score') ?? 0;

            return response()->json([
                'data' => [
                    'student_id' => $student->id,
                    'average_score' => $averageScore
                ],
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
    });

    Route::get('/topics/{topic}/progress', function(Request $request, Topic $topic) {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $student = Student::where('user_id', $request->user_id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the given user ID'
                ], 404);
            }

            $totalLessons = $topic->lessons()->where('is_active', true)->count();

            $completedLessons = StudentLessonProgress::where('student_id', $student->id)
                ->whereHas('lesson', function($q) use ($topic) {
                    $q->where('topic_id', $topic->id);
                })
                ->count();
            
            $progress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

            return response()->json([
                'data' => [
                    'topic_id' => $topic->id,
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'progress_percentage' => round($progress, 2)
                ],
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
    });

    Route::get('/students/progress', function(Request $request) {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]); 

            $student = Student::where('user_id', $request->user_id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the given user ID'
                ], 404);
            }

            $totalLessons = Lesson::where('is_active', true)->count();
            $completedLessons = StudentLessonProgress::where('student_id', $student->id)->count();

            $progress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

            return response()->json([
                'data' => [
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'progress_percentage' => round($progress, 2)
                ],
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
    });

    Route::get('/students/topics-progress', function(Request $request){
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $student = Student::where('user_id', $request->user_id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record associated with the given user ID'
                ], 404);
            }

            $topics = Topic::where('is_active', true)->get();
            $progressData = [];

            foreach ($topics as $topic) {
                $totalLessons = $topic->lessons()->where('is_active', true)->count();
                $completedLessons = StudentLessonProgress::where('student_id', $student->id)
                    ->whereHas('lesson', function($q) use ($topic) {
                        $q->where('topic_id', $topic->id);
                    })
                    ->count();
                
                $progress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

                $progressData[] = [
                    'topic_id' => $topic->id,
                    'topic_title' => $topic->title,
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'progress_percentage' => round($progress, 2)
                ];
            }

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
    });
});

Route::post('/execute-sql', [SqlExecutionController::class, 'executeQuery']);
Route::get('/questions/{id}', [SqlExecutionController::class, 'getQuestion']);

Route::post('/lessons', function(Request $request) {
    try {
        $request->validate([
            'topic_id'=> 'required|exists:topics,id',
            'lesson_title'=> 'required|string|max:255',
            'slug'=> 'nullable|string|max:255|unique:lessons,slug',
            'lesson_content'=> 'nullable|string',
            'estimated_time'=> 'nullable|integer',
            'is_active'=> 'required|boolean',
            'order_index'=> 'nullable|integer',
            'created_by'=> 'required|exists:users,id'
        ]);

        $lesson = Lesson::create([
            'topic_id' => $request->topic_id,
            'lesson_title' => $request->lesson_title,
            'slug' => $request->slug,
            'lesson_content' => $request->lesson_content,
            'estimated_time' => $request->estimated_time,
            'is_active' => $request->is_active,
            'order_index' => $request->order_index,
            'created_by' => $request->created_by
        ]);

        return response()->json([
            'data' => $lesson,
            'message' => 'Lesson created successfully',
            'success' => true,
            'remark' => 'New lesson record created'
        ], 201);
    } catch (ValidationException $e) {
        return response()->json([
            'data' => null,
            'message' => 'Validation failed',
            'success' => false,
            'remark' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'data' => null,
            'message' => 'Failed to create lesson',
            'success' => false,
            'remark' => $e->getMessage()
        ], 500);
    }
});