<?php

namespace App\Repositories\Implementations;

use App\Models\LessonExercise;
use App\Models\StudentLessonProgress;
use App\Models\Lesson;
use App\Repositories\Interfaces\LessonExerciseRepositoryInterface;
use Illuminate\Support\Facades\DB;

class LessonExerciseRepository implements LessonExerciseRepositoryInterface
{
    // ==========================================
    // BASIC CRUD OPERATIONS
    // ==========================================
    
    public function create(array $data)
    {
        return LessonExercise::create($data);
    }

    public function update($id, array $data)
    {
        $lessonExercise = LessonExercise::findOrFail($id);
        $lessonExercise->update($data);
        return $lessonExercise->fresh(['lesson', 'questions']);
    }

    public function delete($id)
    {
        $lessonExercise = LessonExercise::findOrFail($id);
        return $lessonExercise->delete();
    }

    public function findById($id)
    {
        return LessonExercise::with(['lesson', 'questions'])
            ->find($id);
    }

    public function getAll()
    {
        return LessonExercise::with(['lesson' => function($query) {
                $query->select('id', 'lesson_title', 'topic_id');
            }])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'lesson_id' => $exercise->lesson_id,
                    'lesson_title' => $exercise->lesson->lesson_title ?? 'N/A',
                    'is_active' => $exercise->is_active,
                    'questions_count' => $exercise->questions_count,
                    'created_at' => $exercise->created_at,
                    'updated_at' => $exercise->updated_at,
                ];
            });
    }

    // ==========================================
    // LESSON EXERCISE SPECIFIC OPERATIONS
    // ==========================================

    public function getByLesson($lessonId)
    {
        return LessonExercise::where('lesson_id', $lessonId)
            ->with(['lesson', 'questions'])
            ->withCount('questions')
            ->first();
    }

    public function getExerciseWithQuestions($exerciseId)
    {
        $exercise = LessonExercise::with([
                'lesson' => function($query) {
                    $query->select('id', 'lesson_title', 'topic_id');
                },
                'questions' => function($query) {
                    $query->where('is_active', true)
                          ->select('id', 'lesson_exercise_id', 'question_type', 'question_title', 'is_active');
                }
            ])
            ->find($exerciseId);

        if (!$exercise) {
            return null;
        }

        return [
            'id' => $exercise->id,
            'lesson_id' => $exercise->lesson_id,
            'lesson_title' => $exercise->lesson->lesson_title ?? 'N/A',
            'is_active' => $exercise->is_active,
            'questions' => $exercise->questions->map(function($question) {
                return [
                    'id' => $question->id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'correct_answer' => $question->correct_answer,
                    'point' => $question->point,
                ];
            }),
            'total_questions' => $exercise->questions->count(),
            'total_points' => $exercise->questions->sum('point'),
        ];
    }

    public function activate($exerciseId)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        $exercise->update(['is_active' => true]);
        return $exercise->fresh(['lesson', 'questions']);
    }

    public function deactivate($exerciseId)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        $exercise->update(['is_active' => false]);
        return $exercise->fresh(['lesson', 'questions']);
    }

    // ==========================================
    // STUDENT SUBMISSIONS
    // ==========================================

    public function submitExercise($studentId, $exerciseId, $score)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        
        // Create or update student lesson progress
        $progress = StudentLessonProgress::updateOrCreate(
            [
                'student_id' => $studentId,
                'lesson_id' => $exercise->lesson_id,
            ],
            [
                'score' => $score,
                'submitted_at' => now(),
                'finished_at' => now(),
            ]
        );

        // Update student average score
        $this->updateStudentAverageScore($studentId);

        return $progress->fresh(['lesson', 'student']);
    }

    public function getStudentSubmission($studentId, $exerciseId)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        
        return StudentLessonProgress::where('student_id', $studentId)
            ->where('lesson_id', $exercise->lesson_id)
            ->with(['lesson' => function($query) {
                $query->select('id', 'lesson_title', 'topic_id');
            }])
            ->first();
    }

    public function getSubmissionHistory($studentId, $exerciseId)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        
        return StudentLessonProgress::where('student_id', $studentId)
            ->where('lesson_id', $exercise->lesson_id)
            ->with(['lesson' => function($query) {
                $query->select('id', 'lesson_title', 'topic_id');
            }])
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function($progress) {
                return [
                    'id' => $progress->id,
                    'lesson_title' => $progress->lesson->lesson_title ?? 'N/A',
                    'score' => $progress->score,
                    'submitted_at' => $progress->submitted_at,
                    'finished_at' => $progress->finished_at,
                ];
            });
    }

    public function hasStudentCompleted($studentId, $exerciseId)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        
        return StudentLessonProgress::where('student_id', $studentId)
            ->where('lesson_id', $exercise->lesson_id)
            ->exists();
    }

    // ==========================================
    // STATISTICS & ANALYTICS
    // ==========================================

    public function getExerciseStatistics($exerciseId)
    {
        $exercise = LessonExercise::with('lesson')->findOrFail($exerciseId);
        
        $submissions = StudentLessonProgress::where('lesson_id', $exercise->lesson_id)->get();

        if ($submissions->isEmpty()) {
            return [
                'exercise_id' => $exerciseId,
                'lesson_title' => $exercise->lesson->lesson_title ?? 'N/A',
                'total_submissions' => 0,
                'unique_students' => 0,
                'average_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'passing_rate' => 0,
                'score_distribution' => [
                    '0-20' => 0,
                    '21-40' => 0,
                    '41-60' => 0,
                    '61-80' => 0,
                    '81-100' => 0,
                ],
            ];
        }

        $avgScore = $submissions->avg('score') ?? 0;
        $highestScore = $submissions->max('score') ?? 0;
        $lowestScore = $submissions->min('score') ?? 0;
        $passingRate = ($submissions->where('score', '>=', 60)->count() / $submissions->count()) * 100;

        $scoreRanges = [
            '0-20' => $submissions->whereBetween('score', [0, 20])->count(),
            '21-40' => $submissions->whereBetween('score', [21, 40])->count(),
            '41-60' => $submissions->whereBetween('score', [41, 60])->count(),
            '61-80' => $submissions->whereBetween('score', [61, 80])->count(),
            '81-100' => $submissions->whereBetween('score', [81, 100])->count(),
        ];

        return [
            'exercise_id' => $exerciseId,
            'lesson_title' => $exercise->lesson->lesson_title ?? 'N/A',
            'total_submissions' => $submissions->count(),
            'unique_students' => $submissions->unique('student_id')->count(),
            'average_score' => round($avgScore, 2),
            'highest_score' => round($highestScore, 2),
            'lowest_score' => round($lowestScore, 2),
            'passing_rate' => round($passingRate, 2),
            'score_distribution' => $scoreRanges,
        ];
    }

    public function getStudentProgressByExercise($studentId, $exerciseId)
    {
        $exercise = LessonExercise::with('lesson')->findOrFail($exerciseId);
        
        $progress = StudentLessonProgress::where('student_id', $studentId)
            ->where('lesson_id', $exercise->lesson_id)
            ->with(['lesson' => function($query) {
                $query->select('id', 'lesson_title', 'topic_id');
            }, 'student.user'])
            ->first();

        if (!$progress) {
            return null;
        }

        return [
            'student_name' => $progress->student->user->name ?? 'N/A',
            'student_email' => $progress->student->user->email ?? 'N/A',
            'lesson_title' => $progress->lesson->lesson_title ?? 'N/A',
            'score' => $progress->score,
            'submitted_at' => $progress->submitted_at,
            'finished_at' => $progress->finished_at,
        ];
    }

    public function getAverageScore($exerciseId)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        
        return StudentLessonProgress::where('lesson_id', $exercise->lesson_id)
            ->avg('score') ?? 0;
    }

    public function getCompletionRate($exerciseId)
    {
        $exercise = LessonExercise::findOrFail($exerciseId);
        
        $totalStudents = DB::table('students')->count();
        $completedStudents = StudentLessonProgress::where('lesson_id', $exercise->lesson_id)
            ->distinct('student_id')
            ->count('student_id');

        if ($totalStudents == 0) {
            return 0;
        }

        return round(($completedStudents / $totalStudents) * 100, 2);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function updateStudentAverageScore($studentId)
    {
        $avgScore = StudentLessonProgress::where('student_id', $studentId)
            ->avg('score') ?? 0;

        DB::table('students')
            ->where('id', $studentId)
            ->update(['avg_score' => $avgScore]);
    }
}
