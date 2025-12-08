<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\LessonExercise;
use App\Models\StudentLessonProgress;

class LessonExerciseSubmissionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function student_can_submit_lesson_exercise()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        
        $topic = Topic::factory()->create(['is_active' => true]);
        $lesson = $topic->lessons()->create([
            'lesson_title' => 'Test Lesson',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exercise/submit', [
            'user_id' => $student->id,
            'lesson_exercise_id' => $exercise->id,
            'score' => 85.5
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exercise submitted successfully'
            ]);

        $this->assertDatabaseHas('student_lesson_progress', [
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson->id,
            'score' => 85.5
        ]);
    }

    /** @test */
    public function exercise_submission_requires_user_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exercise/submit', [
            'lesson_exercise_id' => 1,
            'score' => 85.5
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function exercise_submission_requires_lesson_exercise_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exercise/submit', [
            'user_id' => $student->id,
            'score' => 85.5
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function exercise_submission_requires_score()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exercise/submit', [
            'user_id' => $student->id,
            'lesson_exercise_id' => 1
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function exercise_submission_validates_score_is_numeric()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exercise/submit', [
            'user_id' => $student->id,
            'lesson_exercise_id' => 1,
            'score' => 'not-a-number'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function exercise_submission_validates_score_minimum()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exercise/submit', [
            'user_id' => $student->id,
            'lesson_exercise_id' => 1,
            'score' => -10
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function exercise_submission_fails_for_nonexistent_exercise()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/exercise/submit', [
            'user_id' => $student->id,
            'lesson_exercise_id' => 99999,
            'score' => 85.5
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function exercise_submission_records_submission_time()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        
        $topic = Topic::factory()->create(['is_active' => true]);
        $lesson = $topic->lessons()->create([
            'lesson_title' => 'Test Lesson',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $this->postJson('/api/exercise/submit', [
            'user_id' => $student->id,
            'lesson_exercise_id' => $exercise->id,
            'score' => 85.5
        ]);

        $progress = StudentLessonProgress::where('student_id', $studentModel->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $this->assertNotNull($progress->submitted_at);
    }
}
