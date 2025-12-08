<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\ChapterExercise;

class ChapterExerciseCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_create_chapter_exercise()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter exercise created successfully'
            ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'topic_id' => $topic->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function chapter_exercise_creation_validates_topic_exists()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => 99999,
            'is_active' => true
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chapter_exercise_can_be_created_as_inactive()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => $topic->id,
            'is_active' => false
        ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'topic_id' => $topic->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function chapter_exercise_defaults_to_inactive_if_not_specified()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => $topic->id
        ]);

        $exercise = ChapterExercise::where('topic_id', $topic->id)->first();
        $this->assertEquals(false, $exercise->is_active);
    }

    /** @test */
    public function chapter_exercise_can_be_created_with_activated_at_date()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        $activatedDate = '2024-01-15 10:00:00';

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => $topic->id,
            'is_active' => true,
            'activated_at' => $activatedDate
        ]);

        $this->assertDatabaseHas('chapter_exercises', [
            'topic_id' => $topic->id,
            'activated_at' => $activatedDate
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_chapter_exercise()
    {
        $student = $this->createStudent();
        $topic = Topic::factory()->create(['is_active' => true]);

        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/chapter-exercises', [
            'user_id' => $student->id,
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function teacher_cannot_create_chapter_exercise()
    {
        $teacher = $this->createTeacher();
        $topic = Topic::factory()->create(['is_active' => true]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/chapter-exercises', [
            'user_id' => $teacher->id,
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function chapter_exercise_creation_requires_topic_id()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'is_active' => true
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function multiple_chapter_exercises_can_be_created_for_same_topic()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => $topic->id,
            'is_active' => false
        ]);

        $exercises = ChapterExercise::where('topic_id', $topic->id)->get();
        $this->assertCount(2, $exercises);
    }

    /** @test */
    public function chapter_exercise_creation_validates_activated_at_is_date()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/chapter-exercises', [
            'user_id' => $admin->id,
            'topic_id' => $topic->id,
            'activated_at' => 'not-a-date'
        ]);

        $response->assertStatus(422);
    }
}
