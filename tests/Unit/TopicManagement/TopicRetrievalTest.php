<?php

namespace Tests\Unit\TopicManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;

class TopicRetrievalTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function authenticated_user_can_get_all_active_topics()
    {
        Topic::factory()->create([
            'topic_name' => 'SQL Basics',
            'is_active' => true,
            'order_index' => 1
        ]);

        Topic::factory()->create([
            'topic_name' => 'Advanced SQL',
            'is_active' => true,
            'order_index' => 2
        ]);

        // Inactive topic should not be returned
        Topic::factory()->create([
            'topic_name' => 'Inactive Topic',
            'is_active' => false
        ]);

        $this->actingAsStudent();

        $response = $this->getJson('/api/topics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Topics retrieved successfully'
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function topics_are_ordered_by_order_index()
    {
        Topic::factory()->create([
            'topic_name' => 'Second Topic',
            'is_active' => true,
            'order_index' => 2
        ]);

        Topic::factory()->create([
            'topic_name' => 'First Topic',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAsStudent();

        $response = $this->getJson('/api/topics');
        $data = $response->json('data');

        $this->assertEquals('First Topic', $data[0]['topic_name']);
        $this->assertEquals('Second Topic', $data[1]['topic_name']);
    }

    /** @test */
    public function inactive_topics_are_not_returned()
    {
        Topic::factory()->create(['topic_name' => 'Active', 'is_active' => true]);
        Topic::factory()->create(['topic_name' => 'Inactive 1', 'is_active' => false]);
        Topic::factory()->create(['topic_name' => 'Inactive 2', 'is_active' => false]);

        $this->actingAsStudent();

        $response = $this->getJson('/api/topics');
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Active', $data[0]['topic_name']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_topics()
    {
        $response = $this->getJson('/api/topics');
        $response->assertStatus(401);
    }
}
