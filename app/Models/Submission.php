<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'student_id', 
        'lesson_exercise_id',
        'question_id', 
        'score'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lessonExercise()
    {
        return $this->belongsTo(LessonExercise::class);
    }
}
