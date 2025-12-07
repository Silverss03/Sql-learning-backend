<?php

namespace Tests\Unit\ExamManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Exam;
use App\Models\StudentExamProgress;

class ExamStartTest extends TestCase
{    use CreatesUsers;

    /** @test */
    public function student_can_start_active_exam()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Active Exam',
            'description' => 'Test exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'is_active' => true,
            'created_by' => $student->id
        ]);

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => $exam->id,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam started successfully'
            ]);

        $this->assertDatabaseHas('student_exam_progress', [
            'student_id' => $student->student->id,
            'exam_id' => $exam->id,
            'is_completed' => false
        ]);
    }    /** @test */
    public function exam_start_requires_exam_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exams/start', [
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }    /** @test */
    public function exam_start_requires_device_fingerprint()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'is_active' => true,
            'created_by' => $student->id
        ]);

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => $exam->id
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }    /** @test */
    public function exam_start_validates_exam_exists()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => 99999,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }    /** @test */
    public function student_cannot_start_inactive_exam()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Future Exam',
            'description' => 'Test exam',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(2),
            'is_active' => false,
            'created_by' => $student->id
        ]);

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => $exam->id,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Exam is not currently active'
            ]);
    }    /** @test */
    public function student_cannot_start_exam_outside_time_window()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Future Exam',
            'description' => 'Test exam',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(2),
            'is_active' => true,
            'created_by' => $student->id
        ]);

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => $exam->id,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Exam is not currently active'
            ]);
    }    /** @test */
    public function student_cannot_start_already_completed_exam()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Completed Exam',
            'description' => 'Test exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'is_active' => true,
            'created_by' => $student->id
        ]);

        StudentExamProgress::create([
            'student_id' => $student->student->id,
            'exam_id' => $exam->id,
            'session_token' => 'test-session-token',
            'device_fingerprint' => 'test-device-123',
            'is_completed' => true,
            'score' => 85,
            'submitted_at' => now()
        ]);

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => $exam->id,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Exam already completed'
            ]);
    }    /** @test */
    public function unauthenticated_user_cannot_start_exam()
    {
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'is_active' => true,
            'created_by' => $student->id
        ]);

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => $exam->id,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(401);
    }    /** @test */
    public function non_student_user_cannot_start_exam()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        
        $this->actingAs($teacher, 'sanctum');

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Test Exam',
            'description' => 'Test exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        $response = $this->postJson('/api/exams/start', [
            'exam_id' => $exam->id,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found'
            ]);
    }   
}
