<?php

namespace Tests\Unit\ClassManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Student;

class ClassDeletionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_delete_class()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'DELETE001',
            'class_name' => 'Delete Me',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/admin/classes/{$class->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Class deleted successfully'
            ]);

        $this->assertDatabaseMissing('classes', [
            'id' => $class->id
        ]);
    }

    /** @test */
    public function deleting_class_removes_student_associations()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'DELETE002',
            'teacher_id' => $teacherModel->id
        ]);

        // Create students in the class
        $student1 = $this->createStudent();
        
        $studentModel1 = Student::where('user_id', $student1->id)->first();
        
        $studentModel1->update(['class_id' => $class->id]);

        $this->actingAs($admin, 'sanctum');

        $this->deleteJson("/api/admin/classes/{$class->id}");

        // Verify students still exist but class association is removed
        $studentModel1->refresh();
        
        $this->assertNull($studentModel1->class_id);
    }

    /** @test */
    public function non_admin_cannot_delete_class()
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'PROTECTED001',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->deleteJson("/api/admin/classes/{$class->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id
        ]);
    }

    /** @test */
    public function teacher_cannot_delete_class()
    {
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'PROTECTED002',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/admin/classes/{$class->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_class_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson('/api/admin/classes/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_class()
    {
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'SECURE001',
            'teacher_id' => $teacherModel->id
        ]);

        $response = $this->deleteJson("/api/admin/classes/{$class->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id
        ]);
    }

    /** @test */
    public function admin_can_batch_delete_classes()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class1 = ClassModel::create([
            'class_code' => 'BATCH001',
            'teacher_id' => $teacherModel->id
        ]);

        $class2 = ClassModel::create([
            'class_code' => 'BATCH002',
            'teacher_id' => $teacherModel->id
        ]);

        $class3 = ClassModel::create([
            'class_code' => 'BATCH003',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes/batch-delete', [
            'class_ids' => [$class1->id, $class2->id, $class3->id]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Classes deleted successfully'
            ])
            ->assertJsonPath('data.deleted_count', 3);

        $this->assertDatabaseMissing('classes', ['id' => $class1->id]);
        $this->assertDatabaseMissing('classes', ['id' => $class2->id]);
        $this->assertDatabaseMissing('classes', ['id' => $class3->id]);
    }

    /** @test */
    public function batch_delete_requires_class_ids_array()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes/batch-delete', [
            'class_ids' => 'not-an-array'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function batch_delete_validates_all_class_ids_exist()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'VALID001',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/classes/batch-delete', [
            'class_ids' => [$class->id, 99999]
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function non_admin_cannot_batch_delete_classes()
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'PROTECTED003',
            'teacher_id' => $teacherModel->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/admin/classes/batch-delete', [
            'class_ids' => [$class->id]
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('classes', ['id' => $class->id]);
    }
}
