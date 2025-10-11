<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'student_id', 
        'question_id', 
        'submitted_sql', 
        'is_correct', 
        'submitted_at', 
        'chosen_answer',
        'question_type'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
