<?php

namespace App\Repositories\Implementations;

use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\StudentLessonProgress;
use App\Repositories\Interfaces\LessonRepositoryInterface;
use App\Models\Question;
use Illuminate\Support\Str;

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

        $questions = Question::whereHas('lessonExercise', function ($query) use ($lessonId) {
                $query->where('lesson_id', $lessonId);
            })
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        return $questions;
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

    public function create(array $data)
    {
        // Generate slug from lesson_title
        if (isset($data['lesson_title'])) {
            $data['slug'] = Str::slug($data['lesson_title']);
        }

        // Set default values
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return Lesson::create($data);
    }

    public function update($id, array $data)
    {
        $lesson = Lesson::findOrFail($id);

        // Update slug if title changed
        if (isset($data['lesson_title'])) {
            $data['slug'] = Str::slug($data['lesson_title']);
        }

        $lesson->update($data);
        return $lesson->fresh();
    }

    public function delete($id)
    {
        $lesson = Lesson::findOrFail($id);
        return $lesson->delete();
    }

    public function findById($id)
    {
        return Lesson::find($id);
    }

    public function getAll($filters = [])
    {
        $query = Lesson::with(['topic', 'lessonExercises'])->where('is_active', true);

        // Filter by topic_id if provided
        if (isset($filters['topic_id'])) {
            $query->where('topic_id', $filters['topic_id']);
        }

        // Order by order_index
        $query->orderBy('order_index', 'asc');

        return $query->get();
    }

    public function getByIdWithDetails($id)
    {
        $lesson = Lesson::with([
            'topic',
            'lessonExercises' => function ($query) {
                $query->where('is_active', true)
                      ->with(['questions' => function ($q) {
                          $q->where('is_active', true)
                            ->with(['multipleChoice', 'interactiveSqlQuestion'])
                            ->orderBy('order_index');
                      }]);
            }
        ])
        ->where('is_active', true)
        ->find($id);

        if (!$lesson) {
            return null;
        }

        return $lesson;
    }
}
