<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultipleChoiceQuestion extends Model
{
    protected $table = 'multiple_choices_questions';
    protected $fillable = [
        'question_id',
        'description',
        'answer_A',
        'answer_B',
        'answer_C',
        'answer_D',
        'correct_answer',
        'is_active'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
