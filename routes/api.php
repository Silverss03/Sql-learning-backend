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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Teacher;
use App\Models\Exam;
use App\Models\ExamAuditLog;
use App\Models\MultipleChoiceQuestion;
use App\Models\Question;
use App\Models\InteractiveSqlQuestion;
use App\Models\StudentExamProgress;

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

            $averageScore = DB::select('
            SELECT AVG(max_score) as average_score FROM (
                SELECT MAX(score) as max_score FROM submissions 
                WHERE student_id = ? AND lesson_exercise_id IS NOT NULL 
                GROUP BY lesson_exercise_id
                UNION ALL
                SELECT MAX(score) as max_score FROM submissions 
                WHERE student_id = ? AND chapter_exercise_id IS NOT NULL 
                GROUP BY chapter_exercise_id
            ) as max_scores
            ', [$student->id, $student->id])[0]->average_score ?? 0;

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
                'exercise_type' => 'required|in:lesson,chapter,exam',
                'parent_id' => 'nullable|integer', // lesson_id or topic_id,
                'exam_title' => 'nullable|string|max:255',
                'exam_description' => 'nullable|string',
                'exam_duration_minutes' => 'nullable|integer|min:1',
                'exam_start_time' => 'nullable|date',
                'exam_end_time' => 'nullable|date',
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
            } elseif ($request->exercise_type === 'chapter') {
                $exercise = ChapterExercise::create([
                    'topic_id' => $request->parent_id,
                    'is_active' => true,
                    'created_by' => $teacher->id
                ]);
            } else {  // exam
                $exercise = Exam::create([
                    'topic_id' => $request->parent_id,  // Nullable
                    'title' => $request->exam_title ?? 'Untitled Exam',
                    'description' => $request->exam_description,
                    'duration_minutes' => $request->exam_duration_minutes ?? 60,
                    'start_time' => $request->exam_start_time ?? now()->addMinutes(10),  // Default to 10 minutes from now
                    'end_time' => $request->exam_end_time ?? now()->addHours(2),  // Default to 2 hours later
                    'is_active' => false,  // Exams start inactive by default
                    'created_by' => $teacher->id
                ]);
            }

            $createdQuestions = [];

            foreach ($request->questions as $questionData) {
                // Create base question
                $question = Question::create([
                    $request->exercise_type === 'lesson' ? 'lesson_exercise_id' :
                    ($request->exercise_type === 'chapter' ? 'chapter_exercise_id' : 'exam_id') => $exercise->id,
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

    // Exams CRUD and Retrieval
    Route::get('/topics/{topic}/exams', function(Topic $topic) {
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

            $exams = Exam::where('topic_id', $topic->id)
                ->where('is_active', true)
                ->leftJoin('student_exam_progress', function ($join) use ($student) {
                    $join->on('exams.id', '=', 'student_exam_progress.exam_id')
                        ->where('student_exam_progress.student_id', '=', $student->id);
                })
                ->select('exams.*',
                        'student_exam_progress.is_completed',
                        'student_exam_progress.score',
                        'student_exam_progress.completed_at')
                ->orderBy('exams.start_time')
                ->get();

            return response()->json([
                'data' => $exams,
                'message' => 'Exams retrieved successfully',
                'success' => true,
                'remark' => 'All exams for the topic'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exams',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/exams/{exam}', function(Exam $exam) {
        try {
            $student = Student::where('user_id', request()->user()->id)->first();

            if(!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $now = now();
            if(!$exam->is_active || $now->lt($exam->start_time) || $now->gt($exam->end_time)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam is not currently active',
                    'success' => false,
                    'remark' => 'The current time is outside the exam schedule'
                ], 403);
            }

            $progress = StudentExamProgress::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();

            if($progress && $progress->is_completed) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam already completed',
                    'success' => false,
                    'remark' => 'The student has already completed this exam'
                ], 403);
            }

            $questions = $exam->questions()->where('is_active', true)->orderBy('order_index')->get();

            $multipleChoiceQuestions = $questions->map(function ($question) {
                return $question->multipleChoice;
            })->filter();

            $sqlQuestions = $questions->map(function ($question) {
                return $question->interactiveSqlQuestion;
            })->filter();

            return response()->json([
                'data' => [
                    'exam' => $exam,
                    'questions' => [
                        'multipleChoice' => $multipleChoiceQuestions,
                        'sqlQuestions' => $sqlQuestions
                    ]
                ],
                'message' => 'Exam retrieved successfully',
                'success' => true,
                'remark' => 'Single exam details'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/exams', function() {
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

            $exams = Exam::where('start_time', '>', now())  // Only future exams
                ->orderBy('start_time')
                ->get()
                ->map(function ($exam) {
                    // Set progress fields to null for future exams (for consistency)
                    return [
                        'id' => $exam->id,
                        'topic_id' => $exam->topic_id,
                        'title' => $exam->title,
                        'description' => $exam->description,
                        'duration_minutes' => $exam->duration_minutes,
                        'start_time' => $exam->start_time,
                        'end_time' => $exam->end_time,
                        'is_active' => $exam->is_active,
                        'created_by' => $exam->created_by,
                        'created_at' => $exam->created_at,
                        'updated_at' => $exam->updated_at,
                        'is_completed' => null,  // Not applicable for future exams
                        'score' => null,         // Not applicable for future exams
                        'submitted_at' => null,  // Not applicable for future exams
                    ];
                });

            return response()->json([
                'data' => $exams,
                'message' => 'Future exams retrieved successfully',
                'success' => true,
                'remark' => 'All active exams scheduled for the future'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exams',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::put('/exams/{exam}', function(Request $request, Exam $exam) {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        if(!$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only teachers can update exams'
            ], 403);
        }

        try {
            $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'duration_minutes' => 'nullable|integer|min:1',
                'start_time' => 'nullable|date:after:now',
                'end_time' => 'nullable|date:after:start_time',
                'is_active' => 'boolean',
            ]);

            $exam->update($request->only(['title', 'description', 'duration_minutes', 'start_time', 'end_time', 'is_active']));

            return response()->json([
                'data' => $exam,
                'message' => 'Exam updated successfully',
                'success' => true,
                'remark' => 'Exam record updated'
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
                'message' => 'Failed to update exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::delete('/exams/{exam}', function(Request $request, Exam $exam) {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        if(!$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only teachers can delete exams'
            ], 403);
        }

        try {
            $exam->delete();

            return response()->json([
                'data' => null,
                'message' => 'Exam deleted successfully',
                'success' => true,
                'remark' => 'Exam record deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    //Student submit exams
    Route::post('/exams/submit', function(Request $request) {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'score' => 'required|numeric|min:0',
                'session_token' => 'required|string',
                'device_fingerprint' => 'required|string',
            ]) ;

            $student = Student::where('user_id', $request->user()->id)->first();

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found for the given user ID',
                    'success' => false,
                    'remark' => 'The user does not have a corresponding student record'
                ], 404);
            }

            $progress = StudentExamProgress::where('student_id', $student->id)
                ->where('exam_id', $request->exam_id)
                ->where('session_token', $request->session_token)
                ->where('device_fingerprint', $request->device_fingerprint)
                ->first();

            if (!$progress || $progress->is_completed) {
                return response()->json([
                    'data' => null,
                    'message' => 'Invalid submission',
                    'success' => false,
                    'remark' => 'Session invalid, exam completed, or device mismatch'
                ], 403);
            }

            $violationCount = ExamAuditLog::where('session_token')
                ->count();

            if($violationCount > 0) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam violations detected',
                    'success' => false,
                    'remark' => 'Cannot submit exam due to recorded violations'
                ], 403);
            }

            DB::beginTransaction();

            $submission = Submission::create([
                'student_id' => $student->id,
                'exam_id' => $request->exam_id,
                'score' => $request->score,
            ]);

            $progress->update([
                'is_completed' => true,
                'score' => $request->score,
                'completed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'data' => $submission,
                'message' => 'Exam submitted successfully',
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
                'message' => 'Failed to submit exam result',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/students/exam-history', function(Request $request) {
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

            $history = StudentExamProgress::where('student_id', $student->id)
                ->with('exam')  // Eager load exam
                ->orderBy('submitted_at', 'desc')
                ->get()
                ->map(function ($progress) {
                    return [
                        'exam_id' => $progress->exam_id,
                        'exam_title' => $progress->exam->title ?? 'No title', 
                        'is_completed' => $progress->is_completed,
                        'score' => $progress->score,
                        'submitted_at' => $progress->submitted_at,
                    ];
                });

            return response()->json([
                'data' => $history,
                'message' => 'Exam history retrieved successfully',
                'success' => true,
                'remark' => 'All exam progress records for the student'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve exam history',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    //Teacher start exam
    Route::put('/exams/{exam}/start', function(Request $request, Exam $exam) {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        if(!$teacher) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized',
                'success' => false,
                'remark' => 'Only teachers can start exams'
            ], 403);
        }

        try {
            if ($exam->is_active) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam is already active',
                    'success' => false,
                    'remark' => 'The exam has already been started'
                ], 400);
            }

            $exam->update(['is_active' => true]);

            return response()->json([
                'data' => $exam,
                'message' => 'Exam started successfully',
                'success' => true,
                'remark' => 'Exam is now active'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to start exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    //Student starts exam
    Route::post('/exams/start', function(Request $request) {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'device_fingerprint' => 'required|string'
            ]);

            $student = Student::where('user_id', $request->user()->id)->first();
            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $exam = Exam::find($request->exam_id);

            $now = now();
            $isActive = $exam->is_active || ($exam->start_time <= $now && $now <= $exam->end_time);
            if (!$isActive) {
                return response()->json([
                    'data' => null,
                    'message' => 'Exam is not currently active',
                    'success' => false,
                    'remark' => 'The current time is outside the exam schedule'
                ], 403);
            }

            // Check for ANY existing progress (completed or not) to prevent duplicates
            $existingProgress = StudentExamProgress::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
            if ($existingProgress) {
                if ($existingProgress->is_completed) {
                    return response()->json([
                        'data' => null,
                        'message' => 'Exam already completed',
                        'success' => false,
                        'remark' => 'The student has already completed this exam'
                    ], 403);
                } else {
                    return response()->json([
                        'data' => null,
                        'message' => 'Exam already started',
                        'success' => false,
                        'remark' => 'The student has already started this exam'
                    ], 403);
                }
            }

            $sessionToken = Str::random(32);

            $progress = StudentExamProgress::create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'is_completed' => false,
                'session_token' => $sessionToken,
                'device_fingerprint' => $request->device_fingerprint,
                'started_at' => $now,
            ]);

            $questions = $exam->questions()->where('is_active', true)->orderBy('order_index')->get();

            $multipleChoiceQuestions = $questions->map(function ($question) {
                return $question->multipleChoice;
            })->filter();

            $sqlQuestions = $questions->map(function ($question) {
                return $question->interactiveSqlQuestion;
            })->filter();

            return response()->json([
                'data' => [
                    'session_token' => $sessionToken,
                    'exam' => [
                        'id' => $exam->id,
                        'title' => $exam->title,
                        'duration_minutes' => $exam->duration_minutes,
                        'start_time' => $exam->start_time,
                        'end_time' => $exam->end_time,
                    ],
                    'questions' => [
                        'multipleChoice' => $multipleChoiceQuestions,
                        'sqlQuestions' => $sqlQuestions
                    ]
                ],
                'message' => 'Exam started successfully',
                'success' => true,
                'remark' => 'Exam session initialized for the student'
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
                'message' => 'Failed to start exam',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    });

    //Audit log during exam
    Route::post('/audit-logs', function(Request $request) {
        try {
            $request->validate([
                'session_token' => 'required|string|exists:student_exam_progress,session_token',
                'details' => 'nullable|array',
                'event_type' => 'nullable|in:app_minimized,app_resumed,screen_capture_detected,tab_switch',
            ]);

            $student = Student::where('user_id', $request->user()->id)->first();
            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'Student not found',
                    'success' => false,
                    'remark' => 'No student record for the authenticated user'
                ], 404);
            }

            $progress = StudentExamProgress::where('session_token', $request->session_token)
                ->where('student_id', $student->id)
                ->first();
            if (!$progress) {
                return response()->json([
                    'data' => null,
                    'message' => 'Invalid session token',
                    'success' => false,
                    'remark' => 'No exam progress found for the given session token'
                ], 404);
            }

            ExamAuditLog::create([
                'session_token' => $request->session_token,
                'student_id' => $progress->student_id,
                'exam_id' => $progress->exam_id,
                'event_type' => $request->event_type,
                'details' => $request->details,
                'logged_at' => now(),
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Audit log recorded successfully',
                'success' => true,
                'remark' => 'Exam audit log entry created'
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
                'message' => 'Failed to record audit log',
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