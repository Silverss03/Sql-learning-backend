<?php

namespace App\Listeners;

use App\Events\ExamCreated;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class SendExamNotification
{

    protected $firebaseService;

    /**
     * Create the event listener.
     */
    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle the event.
     */
    public function handle(ExamCreated $event): void
    {
        try {
            $exam = $event->exam;
            
            // Check if exam has a class assigned
            if (!$exam->class_id) {
                Log::warning("Exam {$exam->id} has no class assigned, skipping notification");
                return;
            }

            // Send notification to all students in the class
            $result = $this->firebaseService->sendExamNotificationToClass(
                $exam->class_id,
                $exam->id,
                $exam->title
            );

            Log::info("Exam notification sent", [
                'exam_id' => $exam->id,
                'class_id' => $exam->class_id,
                'recipients' => $result['recipients_count'],
                'success' => $result['success'],
                'failed' => $result['failed'],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send exam notification: " . $e->getMessage(), [
                'exam_id' => $event->exam->id,
                'exception' => $e,
            ]);
            
            // Don't fail the job, just log the error
            // You can uncomment the line below if you want to retry
            // $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ExamCreated $event, \Throwable $exception): void
    {
        Log::error("SendExamNotification job failed", [
            'exam_id' => $event->exam->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
