<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|confirmed',
                'role' => 'required|in:admin,teacher,student'
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => true
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'Registration successful',
                'success' => true,
                'remark' => 'User registered and authenticated successfully'
            ], 201);

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
                'message' => 'Registration failed',
                'success' => false,
                'remark' => $e->getMessage()
            ], 500);
        }
    }

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

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'data' => null,
                    'message' => 'Invalid credentials',
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
        return response()->json([
            'data' => $request->user(),
            'message' => 'User data retrieved successfully',
            'success' => true,
            'remark' => 'Current authenticated user information'
        ]);
    }
}
