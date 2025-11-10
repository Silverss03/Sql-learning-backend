<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'student_id', 
        'lesson_exercise_id',
        'question_id', 
        'score',
        'chapter_exercise_id',
        'exam_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lessonExercise()
    {
        return $this->belongsTo(LessonExercise::class);
    }

    public function chapterExercise()
    {
        return $this->belongsTo(ChapterExercise::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
