<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAuditLog extends Model
{
    protected $fillable = [
        'student_id',
        'exam_id',
        'session_token',
        'event_type',
    ];

    protected $casts = [
        'details' => 'array',
        'logged_at' => 'datetime',
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