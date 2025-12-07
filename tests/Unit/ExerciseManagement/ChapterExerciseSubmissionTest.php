<?php

namespace Tests\Unit\ExerciseManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\ChapterExercise;
use App\Models\StudentChapterExerciseProgress;

class ChapterExerciseSubmissionTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function student_can_submit_chapter_exercise()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        
        $topic = Topic::factory()->create(['is_active' => true]);
        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/chapter-exercise/submit', [
            'user_id' => $student->id,
            'chapter_exercise_id' => $exercise->id,
            'score' => 92.0
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chapter exercise submitted successfully'
            ]);

        $this->assertDatabaseHas('student_chapter_exercise_progress', [
            'student_id' => $studentModel->id,
            'chapter_exercise_id' => $exercise->id,
            'score' => 92.0
        ]);
    }

    /** @test */
    public function chapter_exercise_submission_requires_user_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/chapter-exercise/submit', [
            'chapter_exercise_id' => 1,
            'score' => 92.0
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chapter_exercise_submission_requires_chapter_exercise_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/chapter-exercise/submit', [
            'user_id' => $student->id,
            'score' => 92.0
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chapter_exercise_submission_requires_score()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/chapter-exercise/submit', [
            'user_id' => $student->id,
            'chapter_exercise_id' => 1
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chapter_exercise_submission_validates_score_is_numeric()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->postJson('/api/chapter-exercise/submit', [
            'user_id' => $student->id,
            'chapter_exercise_id' => 1,
            'score' => 'invalid'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function chapter_exercise_submission_records_submission_time()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        
        $topic = Topic::factory()->create(['is_active' => true]);
        $exercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $this->postJson('/api/chapter-exercise/submit', [
            'user_id' => $student->id,
            'chapter_exercise_id' => $exercise->id,
            'score' => 92.0
        ]);

        $progress = StudentChapterExerciseProgress::where('student_id', $studentModel->id)
            ->where('chapter_exercise_id', $exercise->id)
            ->first();

        $this->assertNotNull($progress->submitted_at);
    }
}
