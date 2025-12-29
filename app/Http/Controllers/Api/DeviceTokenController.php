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
                    'student_id' => $student->id,
                    'device_token' => $request->device_token,
                    'platform' => $request->device_type,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
            }

            // Subscribe to class topic if student has a class
            if ($student->class_id) {
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
            $student = $this->studentRepository->findByUserId($user->id);

            if (!$student) {
                return response()->json([
                    'data' => [],
                    'message' => 'User is not a student',
                    'success' => true,
                    'remark' => 'No device tokens for non-student users'
                ]);
            }

            $tokens = DeviceToken::where('student_id', $student->id)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'data' => $tokens,
                'message' => 'Device tokens retrieved successfully',
                'success' => true,
                'remark' => 'All active device tokens for the student'
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
            $student = $this->studentRepository->findByUserId($user->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'User is not a student',
                    'success' => false,
                    'remark' => 'Cannot delete device tokens for non-student users'
                ], 403);
            }

            $deviceToken = DeviceToken::where('id', $id)
                ->where('student_id', $student->id)
                ->first();

            if (!$deviceToken) {
                return response()->json([
                    'data' => null,
                    'message' => 'Device token not found',
                    'success' => false,
                    'remark' => 'Token does not exist or does not belong to student'
                ], 404);
            }

            // Unsubscribe from topics before deleting
            if ($student->class_id) {
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
            $student = $this->studentRepository->findByUserId($user->id);

            if (!$student) {
                return response()->json([
                    'data' => null,
                    'message' => 'User is not a student',
                    'success' => false,
                    'remark' => 'Cannot delete device tokens for non-student users'
                ], 403);
            }

            $deviceToken = DeviceToken::where('device_token', $request->device_token)
                ->where('student_id', $student->id)
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
            if ($student->class_id) {
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
}
