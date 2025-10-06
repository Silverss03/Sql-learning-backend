<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SQLQuestion;

class QuestionSchemas extends Model
{
    protected $fillable = [
        'schema_name',
        'schema_description',
        'database_name',
        'is_active',
    ];

    public function sqlQuestions()
    {
        return $this->hasMany(SQLQuestion::class, 'question_schema_id');
    }

    public function getDatabaseConnection()
    {
        return $this->database_name;
    }
}
