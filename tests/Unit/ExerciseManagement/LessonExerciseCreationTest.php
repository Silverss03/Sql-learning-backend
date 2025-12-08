<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\LessonExercise;

class LessonExerciseCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_create_lesson_exercise()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Basics',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Basic SQL Exercise',
            'is_active' => true
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson exercise created successfully'
            ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function lesson_exercise_creation_validates_lesson_exists()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => 99999,
            'exercise_title' => 'Exercise',
            'is_active' => true
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function lesson_exercise_can_be_created_as_inactive()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Basics',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Draft Exercise',
            'is_active' => false
        ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'lesson_id' => $lesson->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function lesson_exercise_defaults_to_active_if_not_specified()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Basics',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Exercise'
        ]);

        $exercise = LessonExercise::where('lesson_id', $lesson->id)->first();
        $this->assertEquals(true, $exercise->is_active);
    }

    /** @test */
    public function non_admin_cannot_create_lesson_exercise()
    {
        $student = $this->createStudent();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Basics',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Unauthorized Exercise'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function teacher_cannot_create_lesson_exercise()
    {
        $teacher = $this->createTeacher();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Basics',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Exercise'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function lesson_exercise_creation_requires_lesson_id()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lesson-exercises', [
            'exercise_title' => 'Exercise'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function multiple_exercises_can_be_created_for_same_lesson()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Basics',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Exercise 1'
        ]);

        $this->postJson('/api/admin/lesson-exercises', [
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Exercise 2'
        ]);

        $exercises = LessonExercise::where('lesson_id', $lesson->id)->get();
        $this->assertCount(2, $exercises);
    }
}
