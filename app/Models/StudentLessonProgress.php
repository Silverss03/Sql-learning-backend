<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLessonProgress extends Model
{
    protected $fillable = [
        'student_id',
        'lesson_id',
        'finished_at',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}