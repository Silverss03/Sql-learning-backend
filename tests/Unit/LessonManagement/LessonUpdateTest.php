<?php

namespace Tests\Unit\LessonManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Lesson;
use App\Models\Topic;

class LessonUpdateTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_update_lesson()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Original Title',
            'lesson_content' => 'Original Content',
            'estimated_time' => 20,
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'lesson_title' => 'Updated Title',
            'lesson_content' => 'Updated Content',
            'estimated_time' => 30,
            'order_index' => 2
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson updated successfully'
            ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'lesson_title' => 'Updated Title',
            'lesson_content' => 'Updated Content',
            'estimated_time' => 30,
            'order_index' => 2
        ]);
    }

    /** @test */
    public function admin_can_activate_inactive_lesson()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Inactive Lesson',
            'lesson_content' => 'Content',
            'is_active' => false,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'is_active' => true
        ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_deactivate_active_lesson()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Active Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'is_active' => false
        ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function admin_can_change_lesson_topic()
    {
        $admin = $this->createAdmin();
        $topic1 = Topic::factory()->create(['is_active' => true, 'topic_name' => 'Topic 1']);
        $topic2 = Topic::factory()->create(['is_active' => true, 'topic_name' => 'Topic 2']);
        
        $lesson = Lesson::create([
            'topic_id' => $topic1->id,
            'lesson_title' => 'Movable Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'topic_id' => $topic2->id
        ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'topic_id' => $topic2->id
        ]);
    }

    /** @test */
    public function lesson_update_validates_topic_exists()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Test Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'topic_id' => 99999
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function non_admin_cannot_update_lesson()
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

        $response = $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'lesson_title' => 'Hacked Title'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'lesson_title' => 'Protected Lesson'
        ]);
    }

    /** @test */
    public function teacher_cannot_update_lesson()
    {
        $teacher = $this->createTeacher();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Original',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'lesson_title' => 'Modified'
        ]);        $response->assertStatus(403);
    }

    /** @test */
    public function updating_nonexistent_lesson_fails_gracefully()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson('/api/admin/lessons/99999', [
            'lesson_title' => 'Does Not Exist'
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function slug_updates_when_title_changes()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Original Title',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson("/api/admin/lessons/{$lesson->id}", [
            'lesson_title' => 'New Title'
        ]);

        $lesson->refresh();
        $this->assertEquals('new-title', $lesson->slug);
    }
}
