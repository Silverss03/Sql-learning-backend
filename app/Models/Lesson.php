<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['topic_id', 'lesson_title', 'slug', 'description', 'lesson_content', 'estimated_time', 'is_active', 'order_index', 'created_by'];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
