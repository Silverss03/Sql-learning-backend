<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'topic_id',
        'lesson_title',
        'slug',
        'lesson_content',
        'estimated_time',
        'is_active',
        'order_index',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function lessonExercises()
    {
        return $this->hasMany(LessonExercise::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentLessonProgress::class);
    }

    protected static function boot()
    {
        parent::boot();

        // When a lesson is deleted, delete all related exercises and questions
        static::deleting(function ($lesson) {
            // Delete lesson exercises (which will cascade to questions)
            $lesson->lessonExercises()->each(function ($exercise) {
                $exercise->delete();
            });
            
            // Delete student progress
            $lesson->studentProgress()->delete();
        });
    }
}
