<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentExamProgress extends Model{
    protected $fillable = [
        'student_id',
        'exam_id',
        'is_completed',
        'score',
        'started_at',
        'submitted_at',
        'session_token',
        'device_fingerprint'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}