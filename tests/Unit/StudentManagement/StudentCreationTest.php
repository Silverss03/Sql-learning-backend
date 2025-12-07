<?php

namespace Tests\Unit\StudentManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Hash;

class StudentCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_create_student()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $class = ClassModel::create([
            'class_name' => 'Test Class',
            'description' => 'Description',
            'class_code' => 'Test code',
            'teacher_id' => $teacher->teacher->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/students', [
            'email' => 'newstudent@test.com',
            'password' => 'password123',
            'name' => 'New Student',
            'class_id' => $class->id,
            'student_code' => 'teststudent001'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'newstudent@test.com',
            'role' => 'student'
        ]);

        $user = User::where('email', 'newstudent@test.com')->first();
        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'class_id' => $class->id
        ]);
    }

    /** @test */
    public function student_creation_requires_class_id()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/students', [
            'email' => 'student@test.com',
            'password' => 'password123',
            'name' => 'Student Name',
            'student_code' => 'teststudent001'
            // Missing class_id
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function student_creation_validates_class_exists()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/students', [
            'email' => 'student@test.com',
            'password' => 'password123',
            'name' => 'Student Name',
            'class_id' => 99999 // Non-existent class
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function student_creation_validates_unique_email()
    {
        $admin = $this->createAdmin();
        $existingStudent = $this->createStudent(['email' => 'existing@test.com']);

        $class = ClassModel::first();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/students', [
            'email' => 'existing@test.com',
            'password' => 'password123',
            'name' => 'Student Name',
            'class_id' => $class->id,
            'student_code' => 'teststudent001'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function student_password_is_hashed_on_creation()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $class = ClassModel::create([
            'class_name' => 'Test Class',
            'class_code' => 'Test code',
            'teacher_id' => $teacher->teacher->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/students', [
            'email' => 'student@test.com',
            'password' => 'plainpassword',
            'name' => 'Student Name',
            'class_id' => $class->id,
            'student_code' => 'teststudent001'
        ]);

        $user = User::where('email', 'student@test.com')->first();
        
        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(Hash::check('plainpassword', $user->password));
    }

    /** @test */
    public function non_admin_cannot_create_student()
    {
        $teacher = $this->createTeacher();
        $class = ClassModel::create([
            'class_name' => 'Test Class',
            'class_code' => 'Test code',
            'teacher_id' => $teacher->teacher->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/admin/students', [
            'email' => 'newstudent@test.com',
            'password' => 'password123',
            'name' => 'New Student',
            'class_id' => $class->id,
            'student_code' => 'teststudent001'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function student_user_is_active_on_creation()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $class = ClassModel::create([
            'class_name' => 'Test Class',
            'class_code' => 'Test code',
            'teacher_id' => $teacher->teacher->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/students', [
            'email' => 'student@test.com',
            'password' => 'password123',
            'name' => 'Student Name',
            'class_id' => $class->id,
            'student_code' => 'teststudent001'
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'student@test.com',
            'is_active' => true
        ]);
    }
}
