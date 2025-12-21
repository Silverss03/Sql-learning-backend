<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'user_id', 
        'student_code',
        'avg_score',
        'class_id'
    ] ;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function lessonProgress()
    {
        return $this->hasMany(StudentLessonProgress::class);
    }

    public function chapterExerciseProgress()
    {
        return $this->hasMany(StudentChapterExerciseProgress::class);
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
}
