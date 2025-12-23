<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Build query
            $query = PushNotification::where('user_id', $user->id)
                ->with(['exam:id,title,class_id,start_time,end_time'])
                ->orderBy('created_at', 'desc');
            
            // Filter by type if provided
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }
            
            // Filter by status if provided
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            // Filter by read status
            if ($request->has('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }
            
            // Filter by date range
            if ($request->has('from_date') && $request->from_date) {
                $query->where('created_at', '>=', $request->from_date);
            }
              if ($request->has('to_date') && $request->to_date) {
                $query->where('created_at', '<=', $request->to_date);
            }
            
            // Get only 15 newest notifications
            $notifications = $query->limit(15)->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a single notification by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            
            $notification = PushNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->with(['exam:id,title,class_id,start_time,end_time'])
                ->first();
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Notification retrieved successfully',
                'data' => $notification
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching notification: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a notification by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            $notification = PushNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            
            $notification->delete();
            
            Log::info('Notification deleted', [
                'user_id' => $user->id,
                'notification_id' => $id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete multiple notifications
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyMultiple(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:push_notifications,id'
            ]);
            
            $user = Auth::user();
            $notificationIds = $request->notification_ids;
            
            // Delete only notifications belonging to the authenticated user
            $deletedCount = PushNotification::whereIn('id', $notificationIds)
                ->where('user_id', $user->id)
                ->delete();
            
            Log::info('Multiple notifications deleted', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount,
                'requested_count' => count($notificationIds)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} notification(s)",
                'deleted_count' => $deletedCount
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error deleting multiple notifications: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Clear all notifications for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearAll(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Optionally filter by type before clearing
            $query = PushNotification::where('user_id', $user->id);
            
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }
            
            $deletedCount = $query->delete();
            
            Log::info('All notifications cleared', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount,
                'type_filter' => $request->type ?? 'all'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully cleared {$deletedCount} notification(s)",
                'deleted_count' => $deletedCount
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error clearing notifications: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get notification statistics for the authenticated user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'total' => PushNotification::where('user_id', $user->id)->count(),
                'by_type' => PushNotification::where('user_id', $user->id)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'by_status' => PushNotification::where('user_id', $user->id)
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'recent_count' => PushNotification::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'latest' => PushNotification::where('user_id', $user->id)
                    ->latest()
                    ->first(['id', 'title', 'type', 'created_at'])
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Notification statistics retrieved successfully',
                'data' => $stats
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching notification statistics: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
      /**
     * Get unread notification count
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();
            
            // Count notifications that are explicitly marked as unread
            $count = PushNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Unread count retrieved successfully',
                'data' => [
                    'unread_count' => $count
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching unread count: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark a notification as read
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            $notification = PushNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            
            $notification->markAsRead();
            
            Log::info('Notification marked as read', [
                'user_id' => $user->id,
                'notification_id' => $id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read successfully',
                'data' => $notification->fresh()
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark multiple notifications as read
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markMultipleAsRead(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:push_notifications,id'
            ]);
            
            $user = Auth::user();
            $notificationIds = $request->notification_ids;
            
            // Mark only notifications belonging to the authenticated user as read
            $updatedCount = PushNotification::whereIn('id', $notificationIds)
                ->where('user_id', $user->id)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            
            Log::info('Multiple notifications marked as read', [
                'user_id' => $user->id,
                'updated_count' => $updatedCount,
                'requested_count' => count($notificationIds)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} notification(s) as read",
                'updated_count' => $updatedCount
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error marking multiple notifications as read: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark all notifications as read
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            $updatedCount = PushNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            
            Log::info('All notifications marked as read', [
                'user_id' => $user->id,
                'updated_count' => $updatedCount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} notification(s) as read",
                'updated_count' => $updatedCount
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
