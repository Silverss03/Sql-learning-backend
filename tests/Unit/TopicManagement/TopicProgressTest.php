<?php

namespace Tests\Unit\TopicManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\StudentLessonProgress;

class TopicProgressTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function student_can_get_topic_progress()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $topic = Topic::factory()->create(['is_active' => true]);

        $lesson1 = $topic->lessons()->create([
            'lesson_title' => 'Lesson 1',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        $lesson2 = $topic->lessons()->create([
            'lesson_title' => 'Lesson 2',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/topics/{$topic->id}/progress?user_id={$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Topic progress retrieved successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'topic_id',
                    'total_lessons',
                    'completed_lessons',
                    'progress_percentage'
                ]
            ]);
    }

    /** @test */
    public function topic_progress_calculates_completion_percentage_correctly()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $topic = Topic::factory()->create(['is_active' => true]);

        $lesson1 = $topic->lessons()->create([
            'lesson_title' => 'Lesson 1',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        $lesson2 = $topic->lessons()->create([
            'lesson_title' => 'Lesson 2',
            'lesson_content' => 'Content',
            'is_active' => true
        ]);

        // Complete one out of two lessons
        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson1->id,
            'score' => 85,
            'submitted_at' => now()
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/topics/{$topic->id}/progress?user_id={$student->id}");
        $data = $response->json('data');

        $this->assertEquals(2, $data['total_lessons']);
        $this->assertEquals(1, $data['completed_lessons']);
        $this->assertEquals(50.0, $data['progress_percentage']);
    }

    // /** @test */
    // public function topic_progress_shows_zero_for_no_completed_lessons()
    // {
    //     $student = $this->createStudent();
    //     $topic = Topic::factory()->create(['is_active' => true]);

    //     $topic->lessons()->create([
    //         'lesson_title' => 'Lesson 1',
    //         'lesson_content' => 'Content',
    //         'is_active' => true
    //     ]);

    //     $this->actingAs($student, 'sanctum');

    //     $response = $this->getJson("/api/topics/{$topic->id}/progress?user_id={$student->id}");
    //     $data = $response->json('data');

    //     $this->assertEquals(1, $data['total_lessons']);
    //     $this->assertEquals(0, $data['completed_lessons']);
    //     $this->assertEquals(0.0, $data['progress_percentage']);
    // }

    // /** @test */
    // public function topic_progress_shows_100_for_all_completed_lessons()
    // {
    //     $student = $this->createStudent();
    //     $studentModel = $student->student;
    //     $topic = Topic::factory()->create(['is_active' => true]);

    //     $lesson1 = $topic->lessons()->create([
    //         'lesson_title' => 'Lesson 1',
    //         'lesson_content' => 'Content',
    //         'is_active' => true
    //     ]);

    //     $lesson2 = $topic->lessons()->create([
    //         'lesson_title' => 'Lesson 2',
    //         'lesson_content' => 'Content',
    //         'is_active' => true
    //     ]);

    //     // Complete all lessons
    //     StudentLessonProgress::create([
    //         'student_id' => $studentModel->id,
    //         'lesson_id' => $lesson1->id,
    //         'score' => 85,
    //         'submitted_at' => now()
    //     ]);

    //     StudentLessonProgress::create([
    //         'student_id' => $studentModel->id,
    //         'lesson_id' => $lesson2->id,
    //         'score' => 90,
    //         'submitted_at' => now()
    //     ]);

    //     $this->actingAs($student, 'sanctum');

    //     $response = $this->getJson("/api/topics/{$topic->id}/progress?user_id={$student->id}");
    //     $data = $response->json('data');

    //     $this->assertEquals(100.0, $data['progress_percentage']);
    // }

}
