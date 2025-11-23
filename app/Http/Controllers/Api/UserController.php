<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function uploadAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => 'required|image|max:2048', // max 2MB
            ]);

            $user = $request->user();
            $fileName = $user->id . '_avatar_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();

            // Delete old avatar if exists
            if ($user->image_url) {
                $oldFileName = basename($user->image_url);
                Storage::disk('google')->delete("/user_picture/" . $oldFileName);
            }

            // Upload new avatar
            Storage::disk('google')->putFileAs('/user_picture', $request->file('avatar'), $fileName);
    
            $filePath = Storage::disk('google')->url("/user_picture/" . $fileName);

            $user->update(['image_url' => $filePath]);

            return response()->json([
                'data' => ['avatar_url' => $filePath],
                'message' => 'Avatar updated successfully',
                'success' => true,
                'remark' => 'User avatar stored on Google Drive and URL updated'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'data' => null,
                'message' => 'Validation failed',
                'success' => false,
                'remark' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Failed to upload avatar',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
