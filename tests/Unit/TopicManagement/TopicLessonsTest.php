<?php

namespace Tests\Unit\TopicManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;

class TopicLessonsTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function authenticated_user_can_get_topic_lessons()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $topic->lessons()->create([
            'lesson_title' => 'Lesson 1',
            'lesson_content' => 'Content 1',
            'is_active' => true,
            'order_index' => 1
        ]);

        $topic->lessons()->create([
            'lesson_title' => 'Lesson 2',
            'lesson_content' => 'Content 2',
            'is_active' => true,
            'order_index' => 2
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/topics/{$topic->id}/lessons");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lessons retrieved successfully'
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function lessons_are_ordered_by_order_index()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $topic->lessons()->create([
            'lesson_title' => 'Second Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 2
        ]);

        $topic->lessons()->create([
            'lesson_title' => 'First Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/topics/{$topic->id}/lessons");
        $data = $response->json('data');

        $this->assertEquals('First Lesson', $data[0]['lesson_title']);
        $this->assertEquals('Second Lesson', $data[1]['lesson_title']);
    }

    /** @test */
    public function inactive_lessons_are_not_returned()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $topic->lessons()->create([
            'lesson_title' => 'Active Lesson',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        $topic->lessons()->create([
            'lesson_title' => 'Inactive Lesson',
            'lesson_content' => 'Content',
            'is_active' => false
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/topics/{$topic->id}/lessons");
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Active Lesson', $data[0]['lesson_title']);
    }

    /** @test */
    public function user_cannot_get_lessons_for_inactive_topic()
    {
        $topic = Topic::factory()->create(['is_active' => false]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/topics/{$topic->id}/lessons");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Topic not found or inactive'
            ]);
    }

    /** @test */
    public function user_cannot_get_lessons_for_nonexistent_topic()
    {
        $this->actingAsStudent();

        $response = $this->getJson('/api/topics/99999/lessons');

        $response->assertStatus(404);
    }
}
