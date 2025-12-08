<?php

namespace Tests\Unit\ExamManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentExamProgress;

class ExamDeletionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function teacher_can_delete_exam()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam deleted successfully'
            ]);

        $this->assertDatabaseMissing('exams', [
            'id' => $exam->id
        ]);
    }

    /** @test */
    public function deleting_exam_removes_student_progress_records()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);
        
        // Create student progress
        StudentExamProgress::create([
            'student_id' => $student->student->id,
            'exam_id' => $exam->id,
            'is_completed' => false,
            'session_token' => 'test-token',
            'device_fingerprint' => 'test-fingerprint',
            'started_at' => now()
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('exams', [
            'id' => $exam->id
        ]);
    }

    /** @test */
    public function non_teacher_cannot_delete_exam()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_exam()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);

        $response = $this->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_exam_returns_404()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson('/api/exams/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Exam not found'
            ]);
    }

    /** @test */
    public function teacher_can_delete_active_exam()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Active Exam',
            'description' => 'Test description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('exams', [
            'id' => $exam->id
        ]);
    }

    /** @test */
    public function admin_cannot_delete_exam()
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id
        ]);
    }
}
