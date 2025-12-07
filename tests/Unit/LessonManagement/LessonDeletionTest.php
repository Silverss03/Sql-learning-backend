<?php

namespace Tests\Unit\LessonManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\Question;
use App\Models\LessonExercise;

class LessonDeletionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_delete_lesson()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Delete Me',
            'lesson_content' => 'Content to delete',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/admin/lessons/{$lesson->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson deleted successfully'
            ]);

        $this->assertDatabaseMissing('lessons', [
            'id' => $lesson->id
        ]);
    }

    /** @test */
    public function deleting_lesson_also_deletes_related_exercises_and_questions()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Lesson with Exercise',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $lessonExercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'exercise_title' => 'Exercise 1',
            'is_active' => true
        ]);

        $question = Question::create([
            'lesson_exercise_id' => $lessonExercise->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Test Question',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->deleteJson("/api/admin/lessons/{$lesson->id}");

        // Verify cascade deletion
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseMissing('lesson_exercises', ['id' => $lessonExercise->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_lesson()
    {
        $student = $this->createStudent();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Protected Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->deleteJson("/api/admin/lessons/{$lesson->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id
        ]);
    }

    /** @test */
    public function teacher_cannot_delete_lesson()
    {
        $teacher = $this->createTeacher();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Protected Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/admin/lessons/{$lesson->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_lesson_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson('/api/admin/lessons/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_lesson()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Secure Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $response = $this->deleteJson("/api/admin/lessons/{$lesson->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id
        ]);
    }
}
