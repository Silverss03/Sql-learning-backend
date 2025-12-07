<?php

namespace Tests\Unit\StudentManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Student;

class StudentDeletionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_delete_student()
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent(['email' => 'deleteme@test.com']);
        $studentModel = Student::where('user_id', $student->id)->first();

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/admin/students/{$studentModel->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);

        $this->assertDatabaseMissing('students', [
            'id' => $studentModel->id
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $student->id
        ]);
    }

    /** @test */
    public function deleting_student_also_deletes_user_account()
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent(['email' => 'delete@test.com']);
        $studentModel = Student::where('user_id', $student->id)->first();
        $userId = $student->id;

        $this->actingAs($admin, 'sanctum');
        $this->deleteJson("/api/admin/students/{$studentModel->id}");

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('students', ['user_id' => $userId]);
    }

    /** @test */
    public function non_admin_cannot_delete_student()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent(['email' => 'student@test.com']);
        $studentModel = Student::where('user_id', $student->id)->first();

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/admin/students/{$studentModel->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('students', [
            'id' => $studentModel->id
        ]);
    }
}
