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
use App\Models\Admin;
use App\Models\ChapterExercise;
use App\Models\StudentChapterExerciseProgress;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Teacher;
use App\Models\MultipleChoiceQuestion;
use App\Models\Question;
use App\Models\InteractiveSqlQuestion;

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

    //Get exercises for a lesson
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

    //Submit lesson exercise results
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

    //Get student's average score
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

            $averageScore = DB::table('submissions')
                ->where('student_id', $student->id)
                ->select(DB::raw('
                    AVG(
                        CASE
                            WHEN lesson_exercise_id IS NOT NULL
                            THEN (
                                SELECT MAX(score)
                                FROM submissions s2
                                WHERE s2.student_id = submissions.student_id
                                AND s2.lesson_exercise_id = submissions.lesson_exercise_id
                            )
                            ELSE  score
                        END
                    ) as average_score
                    '))
                ->value('average_score') ?? 0;

            return response()->json([
                'data' => [
                    'student_id' => $student->id,
                    'average_score' => round($averageScore, 2)
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

    //Get topic progress for a student
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

    //Get overall student progress
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

    //Get topics progress for a student
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

            $topics = Topic::where('is_active', true)->withCount(['lessons' => function($q) {
                $q->where('is_active', true);
            }])->get();

            $completedLessons = StudentLessonProgress::where('student_id', $student->id)
            ->join('lessons', 'student_lesson_progress.lesson_id', '=', 'lessons.id')
            ->select('lessons.topic_id', DB::raw('count(*) as completed_count'))
            ->groupBy('lessons.topic_id')
            ->pluck('completed_count', 'topic_id');

            $progressData = $topics->map(function($topic) use ($completedLessons) {
                $completed = $completedLessons[$topic->id] ?? 0;
                $progress = $completedLessons[$topic->id] ?? 0;
                $progress = $topic->lessons_count > 0 ? ($completed / $topic->lessons_count) * 100 : 0;

                return [
                    'topic_id' => $topic->id,
                    'topic_title' => $topic->topic_title,
                    'total_lessons' => $topic->lessons_count,
                    'completed_lessons' => $completed,
                    'progress_percentage' => round($progress, 2)
                ];
            })->values()->all();

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

    // Chapter Exercises CRUD and Retrieval
    Route::post('/chapter-exercises', function(Request $request) {
        $admin = Admin::where('user_id', $request->user_id)->first();

        if(!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can create chapter exercises'
            ], 403);
        }

        try {
            $request->validate([
                'topic_id' => 'required|exists:topics,id',
                'is_active' => 'boolean',
                'activated_at' => 'nullable|date',
            ]);

            $chapterExercise = ChapterExercise::create([
                'topic_id' => $request->topic_id,
                'is_active' => $request->is_active ?? false,
                'created_by' => $admin->id,
                'activated_at' => $request->activated_at,
            ]);

            return response()->json([
                'data' => $chapterExercise,
                'message' => 'Chapter exercise created successfully',
                'success' => true,
                'remark' => 'New chapter exercise record created'
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
                'message' => 'Failed to create chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/topics/{topic}/chapter-exercises', function(Topic $topic) {
        try {
            $student = Student::where('user_id', request()->user()->id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $exercises = ChapterExercise::where('topic_id', $topic->id)
                ->leftJoin('student_chapter_exercise_progress', function ($join) use ($student) {
                    $join->on('chapter_exercises.id', '=', 'student_chapter_exercise_progress.chapter_exercise_id')
                        ->where('student_chapter_exercise_progress.student_id', '=', $student->id);
                })
                ->select('chapter_exercises.*', 
                        'student_chapter_exercise_progress.is_completed',
                        'student_chapter_exercise_progress.score',
                        'student_chapter_exercise_progress.completed_at')
                ->orderBy('chapter_exercises.id') 
                ->get();

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
    });

    Route::get('/chapter-exercises/{chapterExercise}', function(ChapterExercise $chapterExercise) {
        try {
            $questions = $chapterExercise->questions()->where('is_active', true)->orderBy('order_index')->get();

            $multipleChoiceQuestions = $questions->map(function ($question) {
                return $question->multipleChoice;
            })->filter();

            $sqlQuestions = $questions->map(function ($question) {
                return $question->interactiveSqlQuestion;
            })->filter();

            return response()->json([
                'data' => [
                    'chapterExercise' => $chapterExercise,
                    'questions' => [
                        'multipleChoice' => $multipleChoiceQuestions,
                        'sqlQuestions' => $sqlQuestions
                    ]
                ],
                'message' => 'Chapter exercise retrieved successfully',
                'success' => true,
                'remark' => 'Single chapter exercise details'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::put('/chapter-exercises/{chapterExercise}', function(Request $request, ChapterExercise $chapterExercise) {
        $admin = Admin::where('user_id', $request->user_id)->first();

        if(!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can update chapter exercises'
            ], 403);
        }

        try {
            $request->validate([
                'topic_id' => 'nullable|exists:topics,id',
                'is_active' => 'boolean',
                'activated_at' => 'nullable|date',
            ]);

            $chapterExercise->update($request->only(['topic_id', 'is_active', 'activated_at']));

            return response()->json([
                'data' => $chapterExercise,
                'message' => 'Chapter exercise updated successfully',
                'success' => true,
                'remark' => 'Chapter exercise record updated'
            ]);
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
                'message' => 'Failed to update chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::delete('/chapter-exercises/{chapterExercise}', function(Request $request, ChapterExercise $chapterExercise) {
        $admin = Admin::where('user_id', $request->user_id)->first();

        if(!$admin) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only admins can delete chapter exercises'
            ], 403);
        }

        try {
            $chapterExercise->delete();

            return response()->json([
                'data' => null,
                'message' => 'Chapter exercise deleted successfully',
                'success' => true,
                'remark' => 'Chapter exercise record deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete chapter exercise',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    //Submit chapter exercise results
    Route::post('/chapter-exercise/submit', function(Request $request) {
        try {
            $request->validate([
                'chapter_exercise_id' => 'required|exists:chapter_exercises,id',
                'score' => 'required|numeric|min:0',
                'user_id' => 'required|exists:users,id'
            ]) ;

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
                'chapter_exercise_id' => $request->chapter_exercise_id,
                'score' => $request->score,
            ]);

            StudentChapterExerciseProgress::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'chapter_exercise_id' => $request->chapter_exercise_id
                ],
                [
                    'is_completed' => true,
                    'score' => $request->score,
                    'completed_at' => now(),
                ]
            );

            return response()->json([
                'data' => $submission,
                'message' => 'Chapter exercise submitted successfully',
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
                'message' => 'Failed to submit chapter exercise result',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    //Get chapter exercise history
    Route::get('/students/chapter-exercise-history', function(Request $request) {
        try {
            $student = Student::where('user_id', request()->user()->id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            // Retrieve chapter exercise progress with related chapter exercise details
            $history = StudentChapterExerciseProgress::where('student_id', $student->id)
                ->with('chapterExercise')  // Eager load chapter exercise
                ->orderBy('completed_at', 'desc')  // Order by completion date, most recent first
                ->get()
                ->map(function ($progress) {
                    return [
                        'chapter_exercise_id' => $progress->chapter_exercise_id,
                        'chapter_exercise_title' => $progress->chapterExercise->description ?? 'No title', 
                        'is_completed' => $progress->is_completed,
                        'score' => $progress->score,
                        'completed_at' => $progress->completed_at,
                    ];
                });

            return response()->json([
                'data' => $history,
                'message' => 'Chapter exercise history retrieved successfully',
                'success' => true,
                'remark' => 'All chapter exercise progress records for the student'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve chapter exercise history',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::post('/user/avatar', function(Request $request) {
        try {
            $request->validate([
                'avatar' => 'required|image', // max 2MB
            ]);

            $user = $request->user();
            $fileName = $user->id . '_avatar_'. '.' . $request->file('avatar')->getClientOriginalExtension();

            if ($user->image_url) {
                Storage::disk('google')->delete("/user_picture/".$fileName);
            }

            Storage::disk('google')->putFileAs('/user_picture', $request->file('avatar'), $fileName);
    
            $filePath = Storage::disk('google')->url("/user_picture/".$fileName);

            $user->update(['image_url' => $filePath]);

            return response()->json([
                'data' => ['avatar_url' => $filePath],
                'message' => 'Avatar updated successfully',
                'success' => true,
                'remark' => 'User avatar stored on Google Drive and URL updated'
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
                'message' => 'Failed to upload avatar',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::post('/questions', function(Request $request) {
        try {
            $user = $request->user();
            $teacher = Teacher::where('user_id', $user->id)->first();

            if (!$teacher) {
                return response()->json([
                    'data' => null,
                    'message' => 'Unauthorized',
                    'success' => false,
                    'remark' => 'Only teachers can create exercises and questions'
                ], 403);
            }

            DB::beginTransaction();

            // Validate the base request
            $request->validate([
                'exercise_type' => 'required|in:lesson,chapter',
                'parent_id' => 'required|integer', // lesson_id or topic_id
                'questions' => 'required|array|min:1',
                'questions.*.type' => 'required|in:multiple_choice,sql',
                'questions.*.order_index' => 'required|integer',
                'questions.*.title' => 'required|string|max:255',
                // Question type specific validations
                'questions.*.details' => 'required|array',
                'questions.*.details.description' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_A' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_B' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_C' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.answer_D' => 'required_if:questions.*.type,multiple_choice|string',
                'questions.*.details.correct_answer' => 'required_if:questions.*.type,multiple_choice|in:A,B,C,D',
                'questions.*.details.interaction_type' => 'required_if:questions.*.type,interactive_sql',
                'questions.*.details.question_data' => 'required_if:questions.*.type,interactive_sql',
                'questions.*.details.solution_data' => 'required_if:questions.*.type,interactive_sql'
            ]);

            // Create exercise first
            if ($request->exercise_type === 'lesson') {
                $exercise = LessonExercise::create([
                    'lesson_id' => $request->parent_id,
                    'is_active' => true,
                    'created_by' => $teacher->id
                ]);
            } else {
                $exercise = ChapterExercise::create([
                    'topic_id' => $request->parent_id,
                    'is_active' => true,
                    'created_by' => $teacher->id
                ]);
            }

            $createdQuestions = [];

            foreach ($request->questions as $questionData) {
                // Create base question
                $question = Question::create([
                    $request->exercise_type === 'lesson' ? 'lesson_exercise_id' : 'chapter_exercise_id' => $exercise->id,
                    'question_type' => $questionData['type'],
                    'order_index' => $questionData['order_index'],
                    'question_title' => $questionData['title'],
                    'is_active' => true,
                    'created_by' => $teacher->id
                ]);

                // Create specific question type
                if ($questionData['type'] === 'multiple_choice') {
                    $details = MultipleChoiceQuestion::create([
                        'question_id' => $question->id,
                        'description' => $questionData['details']['description'],
                        'answer_A' => $questionData['details']['answer_A'],
                        'answer_B' => $questionData['details']['answer_B'],
                        'answer_C' => $questionData['details']['answer_C'],
                        'answer_D' => $questionData['details']['answer_D'],
                        'correct_answer' => $questionData['details']['correct_answer'],
                        'is_active' => true
                    ]);

                    $question->multipleChoice = $details;
                } else {
                    $details = InteractiveSqlQuestion::create([
                        'question_id' => $question->id,
                        'interaction_type' => $questionData['details']['interaction_type'],
                        'question_data' => $questionData['details']['question_data'],
        'solution_data' => $questionData['details']['solution_data'],
                        'description' => json_encode($questionData['details']['description'] ?? null),
                        'description' => $questionData['details']['description'],
                    ]);

                    $question->interactiveSqlQuestion = $details;
                }

                $createdQuestions[] = $question;
            }

            DB::commit();

            return response()->json([
                'data' => [
                    'exercise' => $exercise,
                    'questions' => $createdQuestions
                ],
                'message' => 'Exercise and questions created successfully',
                'success' => true,
                'remark' => 'New exercise created with ' . count($createdQuestions) . ' questions'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'data' => null,
                'message' => 'Failed to create exercise and questions',
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