<?php

namespace Tests\Unit\LessonManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\LessonExercise;

class LessonRetrievalTest extends TestCase
{
    use CreatesUsers;    
    
    /** @test */
    public function student_can_get_lessons_from_topic()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Lesson 1',
            'lesson_content' => 'Content 1',
            'is_active' => true,
            'order_index' => 1
        ]);

        Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Lesson 2',
            'lesson_content' => 'Content 2',
            'is_active' => true,
            'order_index' => 2
        ]);

        Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Inactive Lesson',
            'lesson_content' => 'Content 3',
            'is_active' => false,
            'order_index' => 3
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/topics/{$topic->id}/lessons");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function student_can_get_lessons_by_topic()
    {
        $topic1 = Topic::factory()->create(['is_active' => true, 'topic_name' => 'SQL Basics']);
        $topic2 = Topic::factory()->create(['is_active' => true, 'topic_name' => 'Advanced SQL']);

        Lesson::create([
            'topic_id' => $topic1->id,
            'lesson_title' => 'SELECT Statement',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        Lesson::create([
            'topic_id' => $topic2->id,
            'lesson_title' => 'Window Functions',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/topics/{$topic1->id}/lessons");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lesson_title', 'SELECT Statement');
    }    

    /** @test */
    public function lessons_are_ordered_by_order_index()
    {
        $topic = Topic::factory()->create(['is_active' => true]);

        Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Third Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 3
        ]);

        Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'First Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 1
        ]);

        Lesson::create([
            'topic_id' => $topic->id,
            'lesson_title' => 'Second Lesson',
            'lesson_content' => 'Content',
            'is_active' => true,
            'order_index' => 2
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/topics/{$topic->id}/lessons");
        $data = $response->json('data');

        $this->assertEquals('First Lesson', $data[0]['lesson_title']);
        $this->assertEquals('Second Lesson', $data[1]['lesson_title']);
        $this->assertEquals('Third Lesson', $data[2]['lesson_title']);
    }   
    
    /** @test */
    public function unauthenticated_user_cannot_access_lessons()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $response = $this->getJson("/api/topics/{$topic->id}/lessons");

        $response->assertStatus(401);
    }    
}
