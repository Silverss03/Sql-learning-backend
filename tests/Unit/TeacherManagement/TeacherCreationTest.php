<?php

namespace Tests\Unit\TeacherManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class TeacherCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_create_teacher()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/teachers', [
            'email' => 'newteacher@test.com',
            'password' => 'password123',
            'name' => 'New Teacher'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Teacher added successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newteacher@test.com',
            'role' => 'teacher'
        ]);

        $user = User::where('email', 'newteacher@test.com')->first();
        $this->assertDatabaseHas('teachers', [
            'user_id' => $user->id
        ]);
    }

    /** @test */
    public function teacher_creation_validates_required_fields()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/teachers', [
            'email' => '',
            'name' => ''
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function teacher_creation_validates_unique_email()
    {
        $admin = $this->createAdmin();
        $existingTeacher = $this->createTeacher(['email' => 'existing@test.com']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/teachers', [
            'email' => 'existing@test.com',
            'password' => 'password123',
            'name' => 'Duplicate Teacher'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function teacher_creation_validates_email_format()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/teachers', [
            'email' => 'invalid-email',
            'password' => 'password123',
            'name' => 'Teacher Name'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function teacher_password_is_hashed_on_creation()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/teachers', [
            'email' => 'teacher@test.com',
            'password' => 'plainpassword',
            'name' => 'Teacher Name'
        ]);

        $user = User::where('email', 'teacher@test.com')->first();
        
        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(Hash::check('plainpassword', $user->password));
    }

    /** @test */
    public function teacher_created_with_default_password_if_not_provided()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/teachers', [
            'email' => 'teacher@test.com',
            'name' => 'Teacher Name'
        ]);

        $user = User::where('email', 'teacher@test.com')->first();
        
        $this->assertTrue(Hash::check('1', $user->password));
    }

    /** @test */
    public function non_admin_cannot_create_teacher()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/admin/teachers', [
            'email' => 'newteacher@test.com',
            'password' => 'password123',
            'name' => 'New Teacher'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function teacher_user_is_active_on_creation()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/teachers', [
            'email' => 'teacher@test.com',
            'password' => 'password123',
            'name' => 'Teacher Name'
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'teacher@test.com',
            'is_active' => true
        ]);
    }
}
