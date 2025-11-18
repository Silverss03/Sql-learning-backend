<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model{
    protected $fillable = [
        'topic_id', 
        'title',
        'description',
        'duration_minutes',
        'start_time',
        'end_time',
        'is_active',
        'created_by',
        'class_id'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentExamProgress::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(ExamAuditLog::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}