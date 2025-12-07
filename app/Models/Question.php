<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'lesson_exercise_id', 'question_type',
        'order_index',
        'is_active',
        'question_title','chapter_exercise_id',
        'exam_id',
        'lesson_id'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function multipleChoice()
    {
        return $this->hasOne(MultipleChoiceQuestion::class);
    }

    public function interactiveSqlQuestion()
    {
        return $this->hasOne(InteractiveSqlQuestion::class, 'question_id');
    }

    public function chapterExercise()
    {
        return $this->belongsTo(ChapterExercise::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function lessonExercise()
    {
        return $this->belongsTo(LessonExercise::class, 'lesson_exercise_id');
    }
}
