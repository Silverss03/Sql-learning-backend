<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\ChapterExercise;

class ChapterExerciseTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function authenticated_user_can_get_chapter_exercise()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $exercise->questions()->create([
            'title' => 'Chapter Question',
            'question_type' => 'multiple_choice',
            'is_active' => true
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/chapter-exercises/{$exercise->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter exercise retrieved successfully'
            ]);
    }

    /** @test */
    public function chapter_exercise_includes_all_active_questions()
    {
        $topic = Topic::factory()->create(['is_active' => true]);
        
        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $exercise->questions()->create([
            'title' => 'Question 1',
            'question_type' => 'multiple_choice',
            'is_active' => true
        ]);

        $exercise->questions()->create([
            'title' => 'Question 2',
            'question_type' => 'sql',
            'is_active' => true
        ]);

        // Inactive question
        $exercise->questions()->create([
            'title' => 'Inactive',
            'question_type' => 'multiple_choice',
            'is_active' => false
        ]);

        $this->actingAsStudent();

        $response = $this->getJson("/api/chapter-exercises/{$exercise->id}");
        $data = $response->json('data');

        $this->assertCount(2, $data['questions']);
    }

    /** @test */
    public function nonexistent_chapter_exercise_returns_404()
    {
        $this->actingAsStudent();

        $response = $this->getJson('/api/chapter-exercises/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Chapter exercise not found'
            ]);
    }
}
