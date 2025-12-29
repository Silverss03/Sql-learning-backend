<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\DeviceToken;
use App\Models\PushNotification;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send notification to a single device
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            
            Log::info("Notification sent successfully to device: {$deviceToken}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send notification to device {$deviceToken}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple devices
     */
    public function sendToMultipleDevices(array $deviceTokens, string $title, string $body, array $data = [])
    {
        if (empty($deviceTokens)) {
            return ['success' => 0, 'failed' => 0];
        }

        $successCount = 0;
        $failedCount = 0;
        $invalidTokens = [];

        foreach ($deviceTokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(Notification::create($title, $body))
                    ->withData($data);

                $this->messaging->send($message);
                $successCount++;
                
                // Update last used timestamp
                $this->updateTokenLastUsed($token);
            } catch (\Exception $e) {
                $failedCount++;
                $errorMessage = $e->getMessage();
                
                Log::error("Failed to send notification to {$token}: {$errorMessage}");
                
                // Check if token is invalid and mark for removal
                if ($this->isInvalidTokenError($errorMessage)) {
                    $invalidTokens[] = $token;
                }
            }
        }

        // Deactivate invalid tokens
        if (!empty($invalidTokens)) {
            $this->deactivateTokens($invalidTokens);
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'invalid_tokens' => $invalidTokens,
        ];
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            
            Log::info("Notification sent successfully to topic: {$topic}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send notification to topic {$topic}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Subscribe device to a topic
     */
    public function subscribeToTopic(string $deviceToken, string $topic)
    {
        try {
            $this->messaging->subscribeToTopic($topic, $deviceToken);
            Log::info("Device subscribed to topic {$topic}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to subscribe device to topic {$topic}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsubscribe device from a topic
     */
    public function unsubscribeFromTopic(string $deviceToken, string $topic)
    {
        try {
            $this->messaging->unsubscribeFromTopic($topic, $deviceToken);
            Log::info("Device unsubscribed from topic {$topic}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to unsubscribe device from topic {$topic}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send exam notification to students in a class
     */
    public function sendExamNotificationToClass(int $classId, int $examId, string $examTitle)
    {
        $title = "Bài thi mới";
        $body = "Bài thi '{$examTitle}' đã được tạo cho lớp của bạn";
        $data = [
            'type' => 'exam_created',
            'exam_id' => (string)$examId,
            'class_id' => (string)$classId,
            'click_action' => 'OPEN_EXAM',
        ];

        // 1. Fetch active device tokens for students in the class
        // Direct query using student_id - much cleaner!
        $deviceTokensData = DeviceToken::whereHas('student', function ($query) use ($classId) {
                $query->where('class_id', $classId);
            })
            ->where('is_active', true)
            ->with('student:id,user_id') // Eager load student with user_id
            ->get(['id', 'student_id', 'device_token']); // Need 'id' for relationships to work!

        if ($deviceTokensData->isEmpty()) {
            return ['success' => 0, 'recipients_count' => 0];
        }

        // 2. Extract plain tokens for Firebase
        $deviceTokens = $deviceTokensData->pluck('device_token')->toArray();

        // 3. Send Notifications FIRST (Batch Send)
        $result = $this->sendToMultipleDevices($deviceTokens, $title, $body, $data);

        // 4. Prepare data for BULK INSERT
        // Get unique user_ids from student->user relationship
        $uniqueUserIds = $deviceTokensData->map(function($token) {
            return $token->student->user_id ?? null;
        })->filter()->unique();
        
        $now = now(); // Timestamp for bulk insert
        $notificationsToInsert = [];

        foreach ($uniqueUserIds as $userId) {
            $notificationsToInsert[] = [
                'user_id' => $userId,
                'exam_id' => $examId,
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data),
                'type' => 'exam_created',
                'status' => 'sent',
                'recipients_count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 5. Run ONE single database query to insert all records
        if (!empty($notificationsToInsert)) {
            PushNotification::insert($notificationsToInsert);
        }

        return array_merge($result, [
            'recipients_count' => count($deviceTokens),
            'notification_records_created' => count($notificationsToInsert),
        ]);
    }

    /**
     * Check if error indicates an invalid token
     */
    private function isInvalidTokenError(string $errorMessage): bool
    {
        $invalidTokenPatterns = [
            'registration-token-not-registered',
            'invalid-registration-token',
            'invalid-argument',
            'mismatched-credential',
        ];

        foreach ($invalidTokenPatterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deactivate invalid tokens
     */
    private function deactivateTokens(array $tokens)
    {
        DeviceToken::whereIn('device_token', $tokens)
            ->update(['is_active' => false]);
        
        Log::info('Deactivated ' . count($tokens) . ' invalid tokens');
    }

    /**
     * Update token last used timestamp
     */
    private function updateTokenLastUsed(string $token)
    {
        DeviceToken::where('device_token', $token)
            ->update(['last_used_at' => now()]);
    }
}
