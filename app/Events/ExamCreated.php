<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;

class ExamCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $exam;

    /**
     * Create a new event instance.
     */
    public function __construct(Exam $exam)
    {
        $this->exam = $exam;
        Log::info('ExamCreated event fired', [
            'exam_id' => $exam->id,
            'exam_title' => $exam->title,
            'class_id' => $exam->class_id
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
