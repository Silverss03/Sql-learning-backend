<?php

namespace Tests\Unit\LessonManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Lesson;
use App\Models\Topic;

class LessonCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function admin_can_create_lesson()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lessons', [
            'topic_id' => $topic->id,
            'lesson_title' => 'Introduction to SQL',
            'lesson_content' => 'This is the content of the lesson',
            'estimated_time' => 30,
            'order_index' => 1,
            'is_active' => true
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson created successfully'
            ]);

        $this->assertDatabaseHas('lessons', [
            'lesson_title' => 'Introduction to SQL',
            'topic_id' => $topic->id,
            'estimated_time' => 30,
            'order_index' => 1,
            'is_active' => true
        ]);
    }

    /** @test */
    public function lesson_creation_validates_required_fields()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lessons', [
            'lesson_title' => '',
            'topic_id' => null
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function lesson_creation_validates_topic_exists()
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lessons', [
            'topic_id' => 99999,
            'lesson_title' => 'SQL Basics',
            'lesson_content' => 'Content',
            'order_index' => 1
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function lesson_has_default_values_when_not_provided()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/lessons', [
            'topic_id' => $topic->id,
            'lesson_title' => 'Basic SQL',
            'lesson_content' => 'Content',
            'order_index' => 1
        ]);

        $lesson = Lesson::where('lesson_title', 'Basic SQL')->first();
        
        $this->assertEquals(true, $lesson->is_active);
    }

    /** @test */
    public function lesson_can_be_created_as_inactive()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/admin/lessons', [
            'topic_id' => $topic->id,
            'lesson_title' => 'Draft Lesson',
            'lesson_content' => 'Draft Content',
            'order_index' => 1,
            'is_active' => false
        ]);

        $this->assertDatabaseHas('lessons', [
            'lesson_title' => 'Draft Lesson',
            'is_active' => false
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_lesson()
    {
        $student = $this->createStudent();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/admin/lessons', [
            'topic_id' => $topic->id,
            'lesson_title' => 'New Lesson',
            'lesson_content' => 'Content',
            'order_index' => 1
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseMissing('lessons', [
            'lesson_title' => 'New Lesson'
        ]);
    }

    /** @test */
    public function teacher_cannot_create_lesson()
    {
        $teacher = $this->createTeacher();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/admin/lessons', [
            'topic_id' => $topic->id,
            'lesson_title' => 'Teacher Lesson',
            'lesson_content' => 'Content',
            'order_index' => 1
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function lesson_creation_validates_estimated_time_is_numeric()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lessons', [
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Lesson',
            'lesson_content' => 'Content',
            'estimated_time' => 'not-a-number',
            'order_index' => 1
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function lesson_creation_validates_order_index_is_numeric()
    {
        $admin = $this->createAdmin();
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/admin/lessons', [
            'topic_id' => $topic->id,
            'lesson_title' => 'SQL Lesson',
            'lesson_content' => 'Content',
            'order_index' => 'invalid'
        ]);

        $response->assertStatus(422);
    }
}
