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

    protected $casts = [
        'question_data' => 'array',
        'solution_data' => 'array'
    ];

    // Mutator for question_data: Encode with pretty print and Unicode preservation
    protected function setQuestionDataAttribute($value)
    {
        $this->attributes['question_data'] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // Mutator for solution_data: Encode with pretty print and Unicode preservation
    protected function setSolutionDataAttribute($value)
    {
        $this->attributes['solution_data'] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
