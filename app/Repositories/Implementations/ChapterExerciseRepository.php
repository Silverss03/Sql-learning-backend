<?php

namespace App\Repositories\Implementations;

use App\Models\ChapterExercise;
use App\Models\StudentChapterExerciseProgress;
use App\Repositories\Interfaces\ChapterExerciseRepositoryInterface;

class ChapterExerciseRepository implements ChapterExerciseRepositoryInterface
{
    public function create(array $data)
    {
        return ChapterExercise::create($data);
    }

    public function findById($id)
    {
        return ChapterExercise::find($id);
    }

    public function update($id, array $data)
    {
        $chapterExercise = $this->findById($id);
        
        if (!$chapterExercise) {
            return null;
        }

        $chapterExercise->update($data);
        
        return $chapterExercise;
    }

    public function delete($id)
    {
        $chapterExercise = $this->findById($id);
        
        if (!$chapterExercise) {
            return false;
        }

        return $chapterExercise->delete();
    }

    public function getWithQuestions($id)
    {
        $chapterExercise = $this->findById($id);
        
        if (!$chapterExercise) {
            return null;
        }

        $questions = $chapterExercise->questions()->where('is_active', true)->orderBy('order_index')->get();

        $multipleChoiceQuestions = $questions->map(function ($question) {
            return $question->multipleChoice;
        })->filter();

        $sqlQuestions = $questions->map(function ($question) {
            return $question->interactiveSqlQuestion;
        })->filter();

        return [
            'chapterExercise' => $chapterExercise,
            'questions' => [
                'multipleChoice' => $multipleChoiceQuestions,
                'sqlQuestions' => $sqlQuestions
            ]
        ];
    }

    public function submitExercise($studentId, $chapterExerciseId, $score)
    {
        return StudentChapterExerciseProgress::create([
            'student_id' => $studentId,
            'chapter_exercise_id' => $chapterExerciseId,
            'score' => $score,
            'submitted_at' => now(),
        ]);
    }

    public function getAllExercises()
    {
        return ChapterExercise::all();
    }
}
