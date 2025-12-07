<?php

namespace Tests\Unit\ClassManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\ClassModel;
use App\Models\Teacher;

class ClassCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_create_class_with_all_fields()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS101',
            'class_name' => 'Introduction to Computer Science',
            'teacher_id' => $teacherModel->id,
            'is_active' => true,
            'semester' => 'Fall 2024',
            'max_students' => 30,
            'academic_year' => '2024-2025'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Class created successfully'
            ]);

        $this->assertDatabaseHas('classes', [
            'class_code' => 'CS101',
            'class_name' => 'Introduction to Computer Science',
            'teacher_id' => $teacherModel->id,
            'is_active' => true,
            'semester' => 'Fall 2024',
            'max_students' => 30,
            'academic_year' => '2024-2025'
        ]);
    }

    /** @test */
    public function admin_can_create_class_with_minimal_fields()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS102',
            'teacher_id' => $teacherModel->id
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Class created successfully'
            ]);

        $this->assertDatabaseHas('classes', [
            'class_code' => 'CS102',
            'teacher_id' => $teacherModel->id
        ]);
    }

    /** @test */
    public function class_creation_requires_class_code()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'teacher_id' => $teacherModel->id,
            'class_name' => 'Test Class'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function class_creation_requires_teacher_id()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS103',
            'class_name' => 'Test Class'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function class_creation_validates_teacher_exists()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS104',
            'teacher_id' => 99999
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function class_is_assigned_to_correct_teacher()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/classes', [
            'class_code' => 'CS105',
            'teacher_id' => $teacherModel->id
        ]);

        $class = ClassModel::where('class_code', 'CS105')->first();
        $this->assertEquals($teacherModel->id, $class->teacher_id);
        $this->assertEquals('teacher@test.com', $class->teacher->user->email);
    }

    /** @test */
    public function non_admin_cannot_create_class()
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS106',
            'teacher_id' => $teacherModel->id
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseMissing('classes', [
            'class_code' => 'CS106'
        ]);
    }

    /** @test */
    public function teacher_cannot_create_class()
    {
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS107',
            'teacher_id' => $teacherModel->id
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_class()
    {
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS108',
            'teacher_id' => $teacherModel->id
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function class_can_be_created_as_inactive()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/classes', [
            'class_code' => 'CS109',
            'teacher_id' => $teacherModel->id,
            'is_active' => false
        ]);

        $this->assertDatabaseHas('classes', [
            'class_code' => 'CS109',
            'is_active' => false
        ]);
    }

    /** @test */
    public function class_creation_validates_max_students_is_numeric()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS110',
            'teacher_id' => $teacherModel->id,
            'max_students' => 'not-a-number'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function multiple_classes_can_be_assigned_to_same_teacher()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/classes', [
            'class_code' => 'CS111',
            'teacher_id' => $teacherModel->id
        ]);

        $this->postJson('/api/admin/classes', [
            'class_code' => 'CS112',
            'teacher_id' => $teacherModel->id
        ]);

        $classes = ClassModel::where('teacher_id', $teacherModel->id)->get();
        $this->assertCount(2, $classes);
    }

    /** @test */
    public function class_response_includes_teacher_information()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['name' => 'John Teacher']);
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes', [
            'class_code' => 'CS113',
            'teacher_id' => $teacherModel->id
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'class_code',
                    'teacher_id',
                    'teacher' => [
                        'id',
                        'user_id',
                        'user' => [
                            'id',
                            'name',
                            'email'
                        ]
                    ]
                ]
            ]);
    }
}
