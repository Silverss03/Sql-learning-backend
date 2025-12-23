<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    protected $fillable = [
        'user_id',
        'exam_id',
        'title',
        'body',
        'data',
        'type',
        'status',
        'is_read',
        'read_at',
        'recipients_count',
        'success_count',
        'failed_count',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Prepare dates for serialization (convert to app timezone)
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return \DateTime::createFromInterface($date)->setTimezone(new \DateTimeZone(config('app.timezone')))->format('Y-m-d H:i:s');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Update delivery stats
     */
    public function updateStats($successCount, $failedCount)
    {
        $this->update([
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);
    }
}
