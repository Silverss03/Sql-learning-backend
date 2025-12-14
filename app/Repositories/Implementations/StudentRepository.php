<?php

namespace App\Repositories\Implementations;

use App\Models\Student;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Models\StudentChapterExerciseProgress;
use App\Models\StudentExamProgress;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StudentRepository implements StudentRepositoryInterface
{
    public function findByUserId($userId)
    {
        return Student::where('user_id', $userId)->first();
    }

    public function getAverageScore($studentId)
    {
        $averageScore = DB::select('
            SELECT AVG(max_score) as average_score FROM (
                SELECT MAX(score) as max_score FROM student_lesson_progress 
                WHERE student_id = ? 
                GROUP BY lesson_id
                UNION ALL
                SELECT MAX(score) as max_score FROM student_chapter_exercise_progress 
                WHERE student_id = ? 
                GROUP BY chapter_exercise_id
                UNION ALL
                SELECT score as max_score FROM student_exam_progress 
                WHERE student_id = ? AND is_completed = true
            ) as max_scores
        ', [$studentId, $studentId, $studentId])[0]->average_score ?? 0;

        $roundedScore = round($averageScore, 2);

        Student::where('id', $studentId)->update([
            'avg_score' => $roundedScore
        ]);

        return [
            'student_id' => $studentId,
            'average_score' => round($averageScore, 2)
        ];
    }

    public function getOverallProgress($studentId)
    {
        $totalLessons = Lesson::where('is_active', true)->count();
        $completedLessons = StudentLessonProgress::where('student_id', $studentId)->count();

        $progress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

        return [
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'progress_percentage' => round($progress, 2)
        ];
    }

    public function getTopicsProgress($studentId)
    {
        $topics = Topic::where('is_active', true)->withCount(['lessons' => function($q) {
            $q->where('is_active', true);
        }])->get();

        $completedLessons = StudentLessonProgress::where('student_id', $studentId)
            ->join('lessons', 'student_lesson_progress.lesson_id', '=', 'lessons.id')
            ->select('lessons.topic_id', DB::raw('count(*) as completed_count'))
            ->groupBy('lessons.topic_id')
            ->pluck('completed_count', 'topic_id');

        return $topics->map(function($topic) use ($completedLessons) {
            $completed = $completedLessons[$topic->id] ?? 0;
            $progress = $topic->lessons_count > 0 ? ($completed / $topic->lessons_count) * 100 : 0;

            return [
                'topic_id' => $topic->id,
                'topic_title' => $topic->topic_title,
                'total_lessons' => $topic->lessons_count,
                'completed_lessons' => $completed,
                'progress_percentage' => round($progress, 2)
            ];
        })->values()->all();
    }

    public function getLessonExerciseHistory($studentId)
    {
        return StudentLessonProgress::where('student_id', $studentId)
            ->with('lesson')  
            ->orderBy('submitted_at', 'desc')  
            ->get()
            ->map(function ($progress) {
                return [
                    'lesson_id' => $progress->lesson_id,
                    'lesson_title' => $progress->lesson->lesson_title ?? 'No title', 
                    'score' => $progress->score,
                    'completed_at' => $progress->completed_at,
                ];
            });
    }

    public function getChapterExerciseHistory($studentId)
    {
        return StudentChapterExerciseProgress::where('student_id', $studentId)
            ->with('chapterExercise')
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($progress) {
                return [
                    'id' => $progress->id,
                    'chapter_exercise_id' => $progress->chapter_exercise_id,
                    'chapter_exercise_title' => $progress->chapterExercise->description ?? 'No title', 
                    'score' => $progress->score,
                    'completed_at' => $progress->submitted_at,
                ];
            });
    }

    public function getExamHistory($studentId)
    {
        return StudentExamProgress::where('student_id', $studentId)
            ->with('exam')
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
    }
}
