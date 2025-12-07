<?php

namespace Tests\Unit\ClassManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\ClassModel;
use App\Models\Teacher;

class ClassUpdateTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_update_class()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS201',
            'class_name' => 'Original Name',
            'teacher_id' => $teacherModel->id,
            'is_active' => true,
            'semester' => 'Fall 2023',
            'max_students' => 20
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/admin/classes/{$class->id}", [
            'class_name' => 'Updated Name',
            'semester' => 'Spring 2024',
            'max_students' => 30
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Class updated successfully'
            ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'class_name' => 'Updated Name',
            'semester' => 'Spring 2024',
            'max_students' => 30
        ]);
    }

    /** @test */
    public function admin_can_change_class_teacher()
    {
        $admin = $this->createAdmin();
        
        $teacher1 = $this->createTeacher(['email' => 'teacher1@test.com']);
        $teacher2 = $this->createTeacher(['email' => 'teacher2@test.com']);
        
        $teacherModel1 = Teacher::where('user_id', $teacher1->id)->first();
        $teacherModel2 = Teacher::where('user_id', $teacher2->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS202',
            'teacher_id' => $teacherModel1->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/classes/{$class->id}", [
            'teacher_id' => $teacherModel2->id
        ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'teacher_id' => $teacherModel2->id
        ]);
    }

    /** @test */
    public function admin_can_activate_inactive_class()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS203',
            'teacher_id' => $teacherModel->id,
            'is_active' => false
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/classes/{$class->id}", [
            'is_active' => true
        ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_deactivate_active_class()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS204',
            'teacher_id' => $teacherModel->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/classes/{$class->id}", [
            'is_active' => false
        ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function class_update_validates_teacher_exists()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS205',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/admin/classes/{$class->id}", [
            'teacher_id' => 99999
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function class_update_validates_max_students_is_numeric()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS206',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/admin/classes/{$class->id}", [
            'max_students' => 'invalid'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function non_admin_cannot_update_class()
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS207',
            'class_name' => 'Original',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->putJson("/api/admin/classes/{$class->id}", [
            'class_name' => 'Hacked'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'class_name' => 'Original'
        ]);
    }

    /** @test */
    public function teacher_cannot_update_class()
    {
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS208',
            'class_name' => 'Original',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/admin/classes/{$class->id}", [
            'class_name' => 'Modified'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function updating_nonexistent_class_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson('/api/admin/classes/99999', [
            'class_name' => 'Does Not Exist'
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_can_update_academic_year()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS209',
            'teacher_id' => $teacherModel->id,
            'academic_year' => '2023-2024'
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/classes/{$class->id}", [
            'academic_year' => '2024-2025'
        ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'academic_year' => '2024-2025'
        ]);
    }

    /** @test */
    public function partial_update_keeps_unchanged_fields()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS210',
            'class_name' => 'Original Name',
            'teacher_id' => $teacherModel->id,
            'semester' => 'Fall 2024',
            'max_students' => 25
        ]);

        $this->actingAs($admin, 'sanctum');

        // Only update class_name
        $this->putJson("/api/admin/classes/{$class->id}", [
            'class_name' => 'New Name'
        ]);

        $class->refresh();
        
        $this->assertEquals('New Name', $class->class_name);
        $this->assertEquals('Fall 2024', $class->semester);
        $this->assertEquals(25, $class->max_students);
        $this->assertEquals($teacherModel->id, $class->teacher_id);
    }
}
