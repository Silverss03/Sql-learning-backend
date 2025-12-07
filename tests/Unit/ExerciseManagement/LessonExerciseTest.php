<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\LessonExercise;

class LessonExerciseTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function authenticated_user_can_get_lesson_exercise()
    {
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

        $exercise->questions()->create([
            'title' => 'Exercise Question',
            'question_type' => 'multiple_choice',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/lessons/{$lesson->id}/exercise");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson exercise and questions retrieved successfully'
            ]);
    }

    /** @test */
    public function lesson_exercise_includes_all_active_questions()
    {
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

        $exercise->questions()->create([
            'title' => 'Question 1',
            'question_type' => 'multiple_choice',
            'is_active' => true
        ]);

        $exercise->questions()->create([
            'title' => 'Question 2',
            'question_type' => 'sql',
            'is_active' => true
        ]);

        // Inactive question
        $exercise->questions()->create([
            'title' => 'Inactive',
            'question_type' => 'multiple_choice',
            'is_active' => false
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/lessons/{$lesson->id}/exercise");
        $data = $response->json('data');

        $this->assertCount(2, $data['questions']);
    }

    /** @test */
    public function inactive_lesson_exercise_returns_404()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        $lesson = $topic->lessons()->create([
            'lesson_title' => 'Test Lesson',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => false
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/lessons/{$lesson->id}/exercise");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Lesson exercise not found or inactive'
            ]);
    }

    /** @test */
    public function lesson_without_exercise_returns_404()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        $lesson = $topic->lessons()->create([
            'lesson_title' => 'Lesson Without Exercise',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/lessons/{$lesson->id}/exercise");

        $response->assertStatus(404);
    }
}
