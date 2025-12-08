<?php

namespace Tests\Unit\ClassManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Student;

class ClassRetrievalTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_get_all_classes()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        ClassModel::create([
            'class_code' => 'CS101',
            'class_name' => 'Class A',
            'teacher_id' => $teacherModel->id
        ]);
        
        ClassModel::create([
            'class_code' => 'CS102',
            'class_name' => 'Class B',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/admin/classes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Classes retrieved successfully'
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function classes_list_shows_all_class_fields()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        ClassModel::create([
            'class_code' => 'CS104',
            'class_name' => 'Complete Class',
            'teacher_id' => $teacherModel->id,
            'is_active' => true,
            'semester' => 'Spring 2024',
            'max_students' => 25,
            'academic_year' => '2023-2024'
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/admin/classes');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.class_code', 'CS104')
            ->assertJsonPath('data.0.class_name', 'Complete Class')
            ->assertJsonPath('data.0.is_active', 1)
            ->assertJsonPath('data.0.semester', 'Spring 2024')
            ->assertJsonPath('data.0.max_students', 25)
            ->assertJsonPath('data.0.academic_year', '2023-2024');
    }

    /** @test */
    public function non_admin_cannot_get_classes_list()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/admin/classes');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function teacher_cannot_get_all_classes_list()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson('/api/admin/classes');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_classes_list()
    {
        $response = $this->getJson('/api/admin/classes');

        $response->assertStatus(401);
    }

    /** @test */
    public function empty_classes_list_returns_successfully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/admin/classes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => []
            ]);
    }

    /** @test */
    public function active_and_inactive_classes_are_both_listed()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        ClassModel::create([
            'class_code' => 'CS107',
            'teacher_id' => $teacherModel->id,
            'is_active' => true
        ]);
        
        ClassModel::create([
            'class_code' => 'CS108',
            'teacher_id' => $teacherModel->id,
            'is_active' => false
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/admin/classes');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
