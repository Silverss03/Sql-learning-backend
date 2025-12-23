<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model{
    protected $fillable = [
        'title',
        'description',
        'duration_minutes',
        'start_time',
        'end_time',
        'is_active',
        'created_by',
        'class_id',
        'topic_id'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Prepare dates for serialization (convert to app timezone)
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return \DateTime::createFromInterface($date)->setTimezone(new \DateTimeZone(config('app.timezone')))->format('Y-m-d H:i:s');
    }

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

    public function pushNotifications()
    {
        return $this->hasMany(PushNotification::class);
    }
}