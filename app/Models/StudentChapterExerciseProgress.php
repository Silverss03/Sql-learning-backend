<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentChapterExerciseProgress extends Model
{
    protected $fillable = [
        'student_id',
        'chapter_exercise_id',
        'score',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function chapterExercise()
    {
        return $this->belongsTo(ChapterExercise::class);
    }
}
