<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChapterExercise extends Model
{
    protected $fillable = [
        'topic_id',
        'is_active',
        'created_by',
        'activated_at',
        'description',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentChapterExerciseProgress::class);
    }
}
