<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLessonProgress extends Model
{
    protected $fillable = [
        'student_id',
        'lesson_id',
        'finished_at',
        'score',
        'submitted_at'
    ];

    protected $casts = [
        'finished_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
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