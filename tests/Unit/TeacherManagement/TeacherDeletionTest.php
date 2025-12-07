<?php

namespace Tests\Unit\TeacherManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Teacher;

class TeacherDeletionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_delete_teacher()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['email' => 'deleteme@test.com']);
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/admin/teachers/{$teacherModel->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ]);

        $this->assertDatabaseMissing('teachers', [
            'id' => $teacherModel->id
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $teacher->id
        ]);
    }

    /** @test */
    public function deleting_teacher_also_deletes_user_account()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['email' => 'delete@test.com']);
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        $userId = $teacher->id;

        $this->actingAs($admin, 'sanctum');
        $this->deleteJson("/api/admin/teachers/{$teacherModel->id}");

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('teachers', ['user_id' => $userId]);
    }

    /** @test */
    public function non_admin_cannot_delete_teacher()
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();

        $this->actingAs($student, 'sanctum');

        $response = $this->deleteJson("/api/admin/teachers/{$teacherModel->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacherModel->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_teacher_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson('/api/admin/teachers/99999');

        $response->assertStatus(404);
    }
}
