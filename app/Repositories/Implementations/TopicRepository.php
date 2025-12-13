<?php

namespace App\Repositories\Implementations;

use App\Models\Topic;
use App\Models\ChapterExercise;
use App\Models\StudentLessonProgress;
use App\Repositories\Interfaces\TopicRepositoryInterface;

class TopicRepository implements TopicRepositoryInterface
{
    public function getAllActive()
    {
        return Topic::where('is_active', true)->orderBy('order_index')->get();
    }

    public function findActiveById($id)
    {
        return Topic::where('id', $id)->where('is_active', true)->first();
    }

    public function getLessonsByTopic($topicId)
    {
        $topic = Topic::find($topicId);
        
        if (!$topic || !$topic->is_active) {
            return null;
        }

        return $topic->lessons()->where('is_active', true)->orderBy('order_index')->get();
    }

    public function getChapterExercisesByTopic($topicId, $studentId)
    {
        return ChapterExercise::where('topic_id', $topicId)
            ->orderBy('chapter_exercises.id') 
            ->get();
    }

    public function getTopicProgress($topicId, $studentId)
    {
        $topic = Topic::find($topicId);
        
        if (!$topic) {
            return null;
        }

        $totalLessons = $topic->lessons()->where('is_active', true)->count();

        $completedLessons = StudentLessonProgress::where('student_id', $studentId)
            ->whereHas('lesson', function($q) use ($topic) {
                $q->where('topic_id', $topic->id);
            })
            ->distinct('lesson_id')
            ->count('lesson_id');
        
        $progress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

        return [
            'topic_id' => $topic->id,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'progress_percentage' => round($progress, 2)
        ];
    }
}
