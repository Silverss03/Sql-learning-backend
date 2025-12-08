<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonExercise extends Model
{
    protected $fillable = ['lesson_id', 'is_active', 'created_at', 'updated_at'];

    /**
     * Define a one-to-one relationship with the Lesson model.
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Define a one-to-many relationship with the Question model.
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'lesson_exercise_id');
    }

    /**
     * Define a many-to-many relationship with the Student model through the Submission pivot table.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'submissions', 'lesson_exercise_id', 'student_id')
                    ->withPivot('submitted_sql', 'is_correct', 'submitted_at', 'chosen_answer', 'question_type', 'score');
    }

    protected static function boot()
    {
        parent::boot();

        // When a lesson exercise is deleted, delete all related questions
        static::deleting(function ($lessonExercise) {
            $lessonExercise->questions()->delete();
        });
    }
}