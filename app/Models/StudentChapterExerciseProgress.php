<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentChapterExerciseProgress extends Model
{
    protected $fillable = [
        'student_id',
        'chapter_exercise_id',
        'is_completed',
        'score',
        'completed_at',
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
