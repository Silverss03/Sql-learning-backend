<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use App\Services\RedisCacheService;

class AuthController extends Controller
{
    protected $cache;
    
    public function __construct(RedisCacheService $cache)
    {
        $this->cache = $cache;
    }
    /**
     * Register a new user
     */
    // public function register(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'name' => 'required|string|max:255',
    //             'email' => 'required|email|unique:users',
    //             'password' => 'required|min:8|confirmed',
    //             'role' => 'required|in:admin,teacher,student'
    //         ]);

    //         $user = User::create([
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'password' => Hash::make($request->password),
    //             'role' => $request->role,
    //             'is_active' => true
    //         ]);

    //         $token = $user->createToken('auth-token')->plainTextToken;

    //         return response()->json([
    //             'data' => [
    //                 'user' => $user,
    //                 'access_token' => $token,
    //                 'token_type' => 'Bearer'
    //             ],
    //             'message' => 'Registration successful',
    //             'success' => true,
    //             'remark' => 'User registered and authenticated successfully'
    //         ], 201);

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'data' => null,
    //             'message' => 'Validation failed',
    //             'success' => false,
    //             'remark' => $e->errors()
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'data' => null,
    //             'message' => 'Registration failed',
    //             'success' => false,
    //             'remark' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if ($user->is_active == false) {
                return response()->json([
                    'data' => null,
                    'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin',
                    'success' => false,
                    'remark' => 'Your account has been deactivated'
                ], 403);
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Email hoặc mật khẩu không đúng',
                    'success' => false,
                    'remark' => 'Email or password is incorrect'
                ], 401);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'Login successful',
                'success' => true,
                'remark' => 'User authenticated successfully'
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
                'message' => 'Login failed',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (revoke all tokens)
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            
            return response()->json([
                'data' => null,
                'message' => 'Logout successful',
                'success' => true,
                'remark' => 'All user tokens have been revoked'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'message' => 'Logout failed',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user details
     */
    public function user(Request $request)
    {
        $userId = $request->user()->id;
        
        // Cache user data for 1 hour
        $user = $this->cache->getUserCache($userId, RedisCacheService::CACHE_LONG);
        
        return response()->json([
            'data' => $user,
            'message' => 'User data retrieved successfully',
            'success' => true,
            'remark' => 'Current authenticated user information (cached)'
        ]);
    }

    /**
     * Send password reset link to user's email
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            // Check if user exists
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'data' => null,
                    'message' => 'Không tìm thấy tài khoản với email này',
                    'success' => false,
                    'remark' => 'User with this email does not exist'
                ], 404);
            }

            $token = Str::random(6);
        
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Send email using the Mailable class
            Mail::to($user->email)->send(new ResetPasswordMail($token, $user->email));

            return response()->json([
                'data' => null,
                'message' => 'Mã xác thực đã được gửi đến email của bạn',
                'success' => true,
                'remark' => 'Password reset token sent to email'
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
                'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset user password using token
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed'
            ]);

            // Check if reset token exists and is valid
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'data' => null,
                    'message' => 'Không tìm thấy yêu cầu đặt lại mật khẩu cho email này',
                    'success' => false,
                    'remark' => 'No password reset request found for this email'
                ], 404);
            }

            // Verify token
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Mã xác thực không đúng',
                    'success' => false,
                    'remark' => 'Invalid verification code'
                ], 400);
            }

            // Check if token is expired (15 minutes)
            $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
            if ($createdAt->addMinutes(15)->isPast()) {
                // Delete expired token
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->delete();

                return response()->json([
                    'data' => null,
                    'message' => 'Mã xác thực đã hết hạn. Vui lòng yêu cầu mã mới',
                    'success' => false,
                    'remark' => 'Verification code has expired. Please request a new one'
                ], 400);
            }

            // Find user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'data' => null,
                    'message' => 'Không tìm thấy tài khoản với email này',
                    'success' => false,
                    'remark' => 'User with this email does not exist'
                ], 404);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->setRememberToken(Str::random(60));
            $user->save();

            // Delete the reset token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();


            return response()->json([
                'data' => null,
                'message' => 'Mật khẩu đã được đặt lại thành công',
                'success' => true,
                'remark' => 'Password has been reset successfully'
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
                'message' => 'Không thể đặt lại mật khẩu. Vui lòng thử lại sau',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed'
            ]);

            $user = $request->user();

            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Mật khẩu hiện tại không đúng',
                    'success' => false,
                    'remark' => 'Current password is incorrect'
                ], 401);
            }

            // Check if new password is different from current password
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại',
                    'success' => false,
                    'remark' => 'New password must be different from current password'
                ], 422);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            // Clear user cache after password change
            $this->cache->clearUserCache($user->id);

            // Revoke all tokens for security
            $user->tokens()->delete();

            return response()->json([
                'data' => null,
                'message' => 'Mật khẩu đã được thay đổi thành công. Vui lòng đăng nhập lại',
                'success' => true,
                'remark' => 'Password changed successfully. Please login again'
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
                'message' => 'Không thể thay đổi mật khẩu. Vui lòng thử lại sau',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }
}
