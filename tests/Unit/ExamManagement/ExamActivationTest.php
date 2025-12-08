<?php

namespace Tests\Unit\ExamManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Exam;

class ExamActivationTest extends TestCase
{
    use CreatesUsers;
    
    /** @test */
    public function teacher_can_activate_exam()
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

        $response = $this->putJson("/api/exams/{$exam->id}/start");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam started successfully',
                'data' => ['is_active' => true]
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'is_active' => true
        ]);
    }
    
    /** @test */
    public function teacher_cannot_activate_already_active_exam()
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
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/exams/{$exam->id}/start");

        $response->assertStatus(400);
    }
    
    /** @test */
    public function non_teacher_cannot_activate_exam()
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();

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

        $response = $this->putJson("/api/exams/{$exam->id}/start");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'is_active' => false
        ]);
    }
    
    /** @test */
    public function unauthenticated_user_cannot_activate_exam()
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();

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

        $response = $this->putJson("/api/exams/{$exam->id}/start");

        $response->assertStatus(401);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'is_active' => false
        ]);
    }
    
    /** @test */
    public function activating_nonexistent_exam_returns_404()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson('/api/exams/99999/start');

        $response->assertStatus(404);
    }
    
    /** @test */
    public function activation_response_includes_exam_data()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Midterm Exam',
            'description' => 'Test description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/exams/{$exam->id}/start");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'is_active'
                ],
                'message',
                'success',
                'remark'
            ]);
    }
}
