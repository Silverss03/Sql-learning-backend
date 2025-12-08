<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\ChapterExercise;

class ChapterExerciseUpdateTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_update_chapter_exercise()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => false
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id,
            'is_active' => true
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter exercise updated successfully'
            ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_activate_inactive_chapter_exercise()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => false
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_deactivate_active_chapter_exercise()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id,
            'is_active' => false
        ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function admin_can_move_chapter_exercise_to_different_topic()
    {
        $admin = $this->createAdmin();
        $topic1 = Topic::factory()->create(['is_active' => true, 'topic_name' => 'Topic 1']);
        $topic2 = Topic::factory()->create(['is_active' => true, 'topic_name' => 'Topic 2']);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic1->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id,
            'topic_id' => $topic2->id
        ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id,
            'topic_id' => $topic2->id
        ]);
    }

    /** @test */
    public function admin_can_update_activated_at_date()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        $newDate = '2024-06-15 14:30:00';

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true,
            'activated_at' => '2024-01-01 10:00:00'
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id,
            'activated_at' => $newDate
        ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id,
            'activated_at' => $newDate
        ]);
    }

    /** @test */
    public function chapter_exercise_update_validates_topic_exists()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id,
            'topic_id' => 99999
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chapter_exercise_update_validates_activated_at_is_date()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $admin->id,
            'activated_at' => 'not-a-date'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function non_admin_cannot_update_chapter_exercise()
    {
        $student = $this->createStudent();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => false
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $student->id,
            'is_active' => true
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'id' => $exercise->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function teacher_cannot_update_chapter_exercise()
    {
        $teacher = $this->createTeacher();
        $topic = Topic::factory()->create(['is_active' => true]);

        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => false
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/chapter-exercises/{$exercise->id}", [
            'user_id' => $teacher->id,
            'is_active' => true
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function updating_nonexistent_chapter_exercise_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson('/api/chapter-exercises/99999', [
            'user_id' => $admin->id,
            'is_active' => true
        ]);

        $response->assertStatus(404);
    }
}
