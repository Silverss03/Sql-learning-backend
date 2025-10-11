<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InteractiveSqlQuestion extends Model
{
    protected $fillable = [
        'question_id',
        'interaction_type',
        'question_data',
        'solution_data',
        'description'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
