<?php

namespace Tests\Unit\ExamManagement;

use Tests\TestCase;
use App\Models\Exam;
use Tests\Traits\CreatesUsers;

class ExamUpdateTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function teacher_can_update_exam()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Original Title',
            'description' => 'Original description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'duration_minutes' => 90
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam updated successfully',
                'data' => [
                    'title' => 'Updated Title',
                    'description' => 'Updated Description',
                    'duration_minutes' => 90
                ]
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'Updated Title'
        ]);
    }
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

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'is_active' => true
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_active' => true]
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function teacher_can_deactivate_exam()
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

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_active' => false]
            ]);
    }
    /** @test */
    public function exam_update_validates_start_time_is_future()
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

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'start_time' => now()->subDay()->toDateTimeString()
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function exam_update_validates_end_time_after_start_time()
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

        $startTime = now()->addDays(2);
        $endTime = now()->addDay(); // End before start

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString()
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function exam_update_validates_duration_is_positive()
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

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'duration_minutes' => 0
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }
    /** @test */
    public function partial_exam_update_preserves_other_fields()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $exam = Exam::create([
            'class_id' => $student->student->class_id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => false,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'title' => 'New Title'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'New Title',
            'description' => 'Original Description',
            'duration_minutes' => 60
        ]);
    }

    /** @test */
    public function non_teacher_cannot_update_exam()
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

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'title' => 'Hacked Title'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_exam()
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

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'title' => 'New Title'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function updating_nonexistent_exam_returns_404()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson('/api/exams/99999', [
            'title' => 'New Title'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Exam not found'
            ]);
    }

    /** @test */
    public function teacher_can_update_exam_times()
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

        $newStartTime = now()->addDays(5);
        $newEndTime = now()->addDays(6);

        $response = $this->putJson("/api/exams/{$exam->id}", [
            'start_time' => $newStartTime->toDateTimeString(),
            'end_time' => $newEndTime->toDateTimeString()
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id
        ]);
    }
}
