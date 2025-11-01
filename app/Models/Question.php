<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['lesson_id', 'question_type' , 'order_index', 'is_active', 'created_by', 'question_title'];

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
}
