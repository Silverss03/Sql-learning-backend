<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\Question;

class LessonExerciseDeletionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_delete_lesson_exercise()
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

        $response = $this->deleteJson("/api/admin/lesson-exercises/{$exercise->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson exercise deleted successfully'
            ]);

        $this->assertDatabaseMissing('lesson_exercises', [
            'id' => $exercise->id
        ]);
    }

    /** @test */
    public function deleting_lesson_exercise_also_deletes_related_questions()
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

        $question = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Test Question',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->deleteJson("/api/admin/lesson-exercises/{$exercise->id}");

        // Verify cascade deletion
        $this->assertDatabaseMissing('lesson_exercises', ['id' => $exercise->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_lesson_exercise()
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
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->deleteJson("/api/admin/lesson-exercises/{$exercise->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id
        ]);
    }

    /** @test */
    public function teacher_cannot_delete_lesson_exercise()
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
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/admin/lesson-exercises/{$exercise->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_lesson_exercise_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson('/api/admin/lesson-exercises/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_lesson_exercise()
    {
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

        $response = $this->deleteJson("/api/admin/lesson-exercises/{$exercise->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('lesson_exercises', [
            'id' => $exercise->id
        ]);
    }
}
