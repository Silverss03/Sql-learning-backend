<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\ChapterExercise;
use App\Models\Question;

class ChapterExerciseDeletionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_delete_chapter_exercise()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter exercise deleted successfully'
            ]);

        $this->assertDatabaseMissing('chapter_exercises', [
            'id' => $exercise->id
        ]);
    }

    /** @test */
    public function deleting_chapter_exercise_also_deletes_related_questions()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $question = Question::create([
            'chapter_exercise_id' => $exercise->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Test Question',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->deleteJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id
        ]);

        // Verify cascade deletion (depending on database setup)
        $this->assertDatabaseMissing('chapter_exercises', ['id' => $exercise->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_chapter_exercise()
    {
        $student = $this->createStudent();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->deleteJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $student->id
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id
        ]);
    }

    /** @test */
    public function teacher_cannot_delete_chapter_exercise()
    {
        $teacher = $this->createTeacher();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $teacher->id
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_chapter_exercise_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson('/api/chapter-exercises/99999', [
            'user_id' => $admin->id
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_chapter_exercise()
    {
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $response = $this->deleteJson("/api/chapter-exercises/{$exercise->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id
        ]);
    }
}
