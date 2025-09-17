<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SQLQuestion extends Model
{
    protected $fillable = ['question_id', 'description', 'sample_answer', 'is_active', 'expected_result', 'setup_sql', 'teardown_sql'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
