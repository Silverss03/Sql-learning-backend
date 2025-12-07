<?php

namespace Tests\Unit\ExamManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Topic;
use App\Models\Exam;
use App\Models\ClassModel;

class ExamRetrievalTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function student_can_get_future_exams_for_their_class()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $class = $studentModel->classModel;
        $teacher = $this->createTeacher();
        
        // Future exam
        Exam::create([
            'class_id' => $class->id,
            'title' => 'Future Exam',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        // Past exam (should not be returned)
        Exam::create([
            'class_id' => $class->id,
            'title' => 'Past Exam',
            'duration_minutes' => 60,
            'start_time' => now()->subDays(1),
            'end_time' => now()->subDays(1)->addHours(2),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/exams');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Future exams retrieved successfully'
            ])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function student_can_get_specific_exam()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $class = $studentModel->classModel;
        $teacher = $this->createTeacher();

        $exam = Exam::create([
            'class_id' => $class->id,
            'title' => 'Test Exam',
            'duration_minutes' => 60,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addHours(2),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/exams/{$exam->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam retrieved successfully'
            ]);
    }

    /** @test */
    public function exams_list_is_ordered_by_start_time()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $class = $studentModel->classModel;
        $teacher = $this->createTeacher();

        Exam::create([
            'class_id' => $class->id,
            'title' => 'Later Exam',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHours(2),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        Exam::create([
            'class_id' => $class->id,
            'title' => 'Earlier Exam',
            'duration_minutes' => 60,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_active' => true,
            'created_by' => $teacher->id
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/exams');
        $data = $response->json('data');

        $this->assertEquals('Earlier Exam', $data[0]['title']);
        $this->assertEquals('Later Exam', $data[1]['title']);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_exams()
    {
        $response = $this->getJson('/api/exams');
        $response->assertStatus(401);
    }
}
