<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\LessonExercise;

class LessonExerciseUpdateTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_update_lesson_exercise()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => false
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/admin/lesson-exercises/{$exercise->id}", [
            'is_active' => true
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson exercise updated successfully'
            ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_activate_inactive_lesson_exercise()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => false
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/lesson-exercises/{$exercise->id}", [
            'is_active' => true
        ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_deactivate_active_lesson_exercise()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/lesson-exercises/{$exercise->id}", [
            'is_active' => false
        ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function admin_can_move_exercise_to_different_lesson()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson1 = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Lesson 1',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $lesson2 = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Lesson 2',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 2
        ]);

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson1->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/lesson-exercises/{$exercise->id}", [
            'lesson_id' => $lesson2->id
        ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id,
            'lesson_id' => $lesson2->id
        ]);
    }

    /** @test */
    public function lesson_exercise_update_validates_lesson_exists()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/admin/lesson-exercises/{$exercise->id}", [
            'lesson_id' => 99999
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function non_admin_cannot_update_lesson_exercise()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => false
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->putJson("/api/admin/lesson-exercises/{$exercise->id}", [
            'is_active' => true
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function teacher_cannot_update_lesson_exercise()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => false
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/admin/lesson-exercises/{$exercise->id}", [
            'is_active' => true
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function updating_nonexistent_lesson_exercise_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson('/api/admin/lesson-exercises/99999', [
            'is_active' => true
        ]);

        $response->assertStatus(404);
    }
}
