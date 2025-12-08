<?php

namespace Tests\Unit\ExamManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\Exam;
use App\Models\StudentExamProgress;

class ExamSubmissionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function student_can_start_exam()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $class = $studentModel->classModel;
        $teacher = $this->createTeacher();

        $exam = Exam::create([
            'class_id' => $class->id,
            'title' => 'Test Exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHours(1),
            'end_time' => now()->addHours(1),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exams/start', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'device_fingerprint' => 'test-device-123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam started successfully'
            ]);

        $this->assertDatabaseHas('student_exam_progress', [
            'student_id' => $studentModel->id,
            'exam_id' => $exam->id,
            'is_completed' => false
        ]);
    }

    /** @test */
    public function student_can_submit_exam()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $class = $studentModel->classModel;
        $teacher = $this->createTeacher();        

        $exam = Exam::create([
            'class_id' => $class->id,
            'title' => 'Test Exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHours(1),
            'end_time' => now()->addHours(1),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        // Start exam first
        StudentExamProgress::create([
            'student_id' => $studentModel->id,
            'exam_id' => $exam->id,
            'start_time' => now(),
            'is_completed' => false,
            'session_token' => 'test-session-token',
            'device_fingerprint' => 'test-device-123'
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exams/submit', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => 88.5,
            'device_fingerprint' => 'test-device-123',
            'session_token' => 'test-session-token'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam submitted successfully'
            ]);

        $this->assertDatabaseHas('student_exam_progress', [
            'student_id' => $studentModel->id,
            'exam_id' => $exam->id,
            'score' => 88.5,
            'is_completed' => true
        ]);
    }

    /** @test */
    public function exam_submission_requires_user_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exams/submit', [
            'exam_id' => 1,
            'score' => 88.5
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function exam_submission_requires_exam_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exams/submit', [
            'user_id' => $student->id,
            'score' => 88.5
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function exam_submission_requires_score()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exams/submit', [
            'user_id' => $student->id,
            'exam_id' => 1
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function student_cannot_submit_exam_twice()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $class = $studentModel->classModel;
        $teacher = $this->createTeacher();        

        $exam = Exam::create([
            'class_id' => $class->id,
            'title' => 'Test Exam',
            'duration_minutes' => 60,
            'start_time' => now()->subHours(1),
            'end_time' => now()->addHours(1),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        // Already completed
        StudentExamProgress::create([
            'student_id' => $studentModel->id,
            'exam_id' => $exam->id,
            'start_time' => now()->subMinutes(30),
            'end_time' => now(),
            'score' => 90,
            'is_completed' => true,
            'session_token' => 'test-session-token',
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/exams/{$exam->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Exam already completed'
            ]);
    }
}
