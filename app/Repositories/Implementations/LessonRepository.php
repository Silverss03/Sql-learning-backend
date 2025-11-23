<?php

namespace App\Repositories\Implementations;

use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\StudentLessonProgress;
use App\Repositories\Interfaces\LessonRepositoryInterface;

class LessonRepository implements LessonRepositoryInterface
{
    public function findActiveById($id)
    {
        return Lesson::where('id', $id)->where('is_active', true)->first();
    }

    public function getQuestionsByLesson($lessonId)
    {
        $lesson = $this->findActiveById($lessonId);
        
        if (!$lesson) {
            return null;
        }

        return $lesson->questions()->where('is_active', true)->orderBy('order_index')->get();
    }

    public function getExerciseByLesson($lessonId)
    {
        $lessonExercise = LessonExercise::where('lesson_id', $lessonId)
            ->where('is_active', true)
            ->first();

        if (!$lessonExercise) {
            return null;
        }

        $questions = $lessonExercise->questions()->where('is_active', true)->get();

        $multipleChoiceQuestions = $questions->map(function ($question) {
            return $question->multipleChoice;
        })->filter();

        $sqlQuestions = $questions->map(function ($question) {
            return $question->interactiveSqlQuestion;
        })->filter();

        return [
            'lessonExercise' => $lessonExercise,
            'questions' => [
                'multipleChoice' => $multipleChoiceQuestions,
                'sqlQuestions' => $sqlQuestions
            ]
        ];
    }

    public function submitExercise($studentId, $lessonExerciseId, $score)
    {
        $lessonExercise = LessonExercise::find($lessonExerciseId);
        $lessonId = $lessonExercise->lesson_id;

        return StudentLessonProgress::create([
            'student_id' => $studentId,
            'lesson_id' => $lessonId,
            'score' => $score,
            'submitted_at' => now(),
            'finished_at' => now(),
        ]);
    }
}
