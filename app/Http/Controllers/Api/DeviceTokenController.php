<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class DeviceTokenController extends Controller
{
    protected $studentRepository;
    protected $firebaseService;

    public function __construct(
        StudentRepositoryInterface $studentRepository,
        FirebaseNotificationService $firebaseService
    ) {
        $this->studentRepository = $studentRepository;
        $this->firebaseService = $firebaseService;
    }

    /**
     * Register/Update device token for the authenticated user
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'device_token' => 'required|string',
                'device_type' => 'required|in:android,ios,web',
            ]);

            $user = $request->user();
            $student = $this->studentRepository->findByUserId($user->id);

            // Check if token already exists
            $deviceToken = DeviceToken::where('device_token', $request->device_token)->first();

            if ($deviceToken) {
                // Update existing token
                $deviceToken->update([
                    'user_id' => $user->id,
                    'student_id' => $student ? $student->id : null,
                    'platform' => $request->device_type,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
            } else {
                // Create new token
                $deviceToken = DeviceToken::create([
                    'user_id' => $user->id,
                    'student_id' => $student ? $student->id : null,
                    'device_token' => $request->device_token,
                    'platform' => $request->device_type,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
            }

            // Subscribe to class topic if student has a class
            if ($student && $student->class_id) {
                $topic = "class_{$student->class_id}";
                $this->firebaseService->subscribeToTopic($request->device_token, $topic);
            }

            return response()->json([
                'data' => $deviceToken,
                'message' => 'Device token registered successfully',
                'success' => true,
                'remark' => 'Token saved and ready to receive notifications'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Device token registration failed: ' . $e->getMessage());
            return response()->json([
                'data' => null,
                'message' => 'Failed to register device token',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all device tokens for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $tokens = DeviceToken::where('user_id', $user->id)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'data' => $tokens,
                'message' => 'Device tokens retrieved successfully',
                'success' => true,
                'remark' => 'All active device tokens for the user'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to retrieve device tokens',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific device token
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $deviceToken = DeviceToken::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$deviceToken) {
                return response()->json([
                    'data' => null,
                    'message' => 'Device token not found',
                    'success' => false,
                    'remark' => 'Token does not exist or does not belong to user'
                ], 404);
            }

            // Unsubscribe from topics before deleting
            $student = $this->studentRepository->findByUserId($user->id);
            if ($student && $student->class_id) {
                $topic = "class_{$student->class_id}";
                $this->firebaseService->unsubscribeFromTopic($deviceToken->device_token, $topic);
            }

            $deviceToken->delete();

            return response()->json([
                'data' => null,
                'message' => 'Device token deleted successfully',
                'success' => true,
                'remark' => 'Token removed from database'
            ]);
        } catch (\Exception $e) {
            Log::error('Device token deletion failed: ' . $e->getMessage());
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete device token',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete device token by token string (for logout)
     */
    public function deleteByToken(Request $request)
    {
        try {
            $request->validate([
                'device_token' => 'required|string',
            ]);

            $user = $request->user();
            $deviceToken = DeviceToken::where('device_token', $request->device_token)
                ->where('user_id', $user->id)
                ->first();

            if (!$deviceToken) {
                return response()->json([
                    'data' => null,
                    'message' => 'Device token not found',
                    'success' => false,
                    'remark' => 'Token does not exist'
                ], 404);
            }

            // Unsubscribe from topics
            $student = $this->studentRepository->findByUserId($user->id);
            if ($student && $student->class_id) {
                $topic = "class_{$student->class_id}";
                $this->firebaseService->unsubscribeFromTopic($request->device_token, $topic);
            }

            $deviceToken->delete();

            return response()->json([
                'data' => null,
                'message' => 'Device token deleted successfully',
                'success' => true,
                'remark' => 'Token removed on logout'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Device token deletion failed: ' . $e->getMessage());
            return response()->json([
                'data' => null,
                'message' => 'Failed to delete device token',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification (for debugging)
     */
    public function testNotification(Request $request)
    {
        try {
            $request->validate([
                'device_token' => 'required|string',
                'title' => 'string',
                'body' => 'string',
            ]);

            $title = $request->title ?? 'Test Notification';
            $body = $request->body ?? 'This is a test notification from SQL Learning App';

            $result = $this->firebaseService->sendToDevice(
                $request->device_token,
                $title,
                $body,
                ['type' => 'test']
            );

            if ($result) {
                return response()->json([
                    'data' => ['sent' => true],
                    'message' => 'Test notification sent successfully',
                    'success' => true,
                    'remark' => 'Check your device for the notification'
                ]);
            } else {
                return response()->json([
                    'data' => ['sent' => false],
                    'message' => 'Failed to send test notification',
                    'success' => false,
                    'remark' => 'Check logs for more details'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to send test notification',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
