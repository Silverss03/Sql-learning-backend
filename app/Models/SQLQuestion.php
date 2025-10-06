<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SQLQuestion extends Model
{
    protected $table = 'sql_questions';
    protected $fillable = [
        'question_id', 
        'description', 
        'sample_answer', 
        'is_active', 
        'expected_result', 
        'question_schema_id',
        'expected_result_query'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function questionSchema()
    {
        return $this->belongsTo(QuestionSchemas::class, 'question_schema_id');
    }
}
