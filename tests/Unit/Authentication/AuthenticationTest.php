<?php

namespace Tests\Unit\Authentication;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = $this->createStudent([
            'email' => 'student@test.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'student@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function login_fails_with_invalid_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function login_fails_with_invalid_password()
    {
        $this->createStudent([
            'email' => 'student@test.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'student@test.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function login_requires_email()
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function login_requires_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_user_can_get_their_profile()
    {
        $user = $this->createStudent(['name' => 'John Doe', 'email' => 'john@test.com']);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@test.com',
                    'role' => 'student'
                ]
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = $this->createStudent();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function login_returns_correct_user_data()
    {
        $user = $this->createStudent([
            'name' => 'Jane Doe',
            'email' => 'jane@test.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'name' => 'Jane Doe',
                        'email' => 'jane@test.com'
                    ],
                    'token_type' => 'Bearer'
                ]
            ]);
    }

}
