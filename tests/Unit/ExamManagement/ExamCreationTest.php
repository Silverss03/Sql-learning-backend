<?php

namespace Tests\Unit\ExamManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Exam;
use App\Models\ClassModel;

class ExamCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function teacher_can_create_exam_with_questions()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Midterm Exam',
            'exam_description' => 'This is a midterm exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'What is SQL?',
                    'details' => [
                        'description' => 'Select the correct answer',
                        'answer_A' => 'Structured Query Language',
                        'answer_B' => 'Simple Query Language',
                        'answer_C' => 'Standard Query Language',
                        'answer_D' => 'System Query Language',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Exercise and questions created successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'exercise',
                    'questions'
                ]
            ]);

        $this->assertDatabaseHas('exams', [
            'title' => 'Midterm Exam',
            'class_id' => $student->student->class_id,
            'created_by' => $teacher->id
        ]);
    }    

    /** @test */
    public function exam_creation_requires_duration_minutes()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }

    /** @test */
    public function exam_creation_requires_start_time()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 60,
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }

    /** @test */
    public function exam_creation_requires_end_time()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }    /** @test */
    public function exam_creation_validates_duration_is_numeric()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 'not-a-number',
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }

    /** @test */
    public function exam_creation_validates_class_exists()
    {
        $teacher = $this->createTeacher();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => 99999,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }   
     
    /** @test */
    public function exam_creation_requires_questions()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }

    /** @test */
    public function exam_creation_requires_at_least_one_question()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => []
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }

    /** @test */
    public function non_teacher_cannot_create_exam()
    {
        $student = $this->createStudent();

        $this->actingAs($student, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        $this->assertDatabaseMissing('exams', [
            'title' => 'Test Exam'
        ]);
    }

    /** @test */
    public function admin_cannot_create_exam()
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $this->actingAs($admin, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Admin Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('exams', [
            'title' => 'Admin Exam'
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_exam()
    {
        $student = $this->createStudent();

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Unauth Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('exams', [
            'title' => 'Unauth Exam'
        ]);
    }

    /** @test */
    public function exam_creation_sets_created_by_to_authenticated_teacher()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Creator Test Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('exams', [
            'title' => 'Creator Test Exam',
            'created_by' => $teacher->id
        ]);
    }

    /** @test */
    public function exam_creation_with_description_is_optional()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'No Description Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(201);

        $exam = Exam::where('title', 'No Description Exam')->first();
        $this->assertNull($exam->description);
    }

    /** @test */
    public function exam_creation_validates_end_time_is_after_start_time()
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->actingAs($teacher, 'sanctum');

        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $student->student->class_id,
            'exam_title' => 'Test Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->format('Y-m-d H:i:s'), // End time before start time
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        $response->assertStatus(422);
    }

    /** @test */
    public function teacher_can_only_create_exam_for_own_class()
    {
        // Create two teachers
        $teacher1 = $this->createTeacher(['email' => 'teacher1@test.com']);
        $teacher2 = $this->createTeacher(['email' => 'teacher2@test.com']);

        // Create a class explicitly for teacher2
        $classForTeacher2 = ClassModel::create([
            'class_name' => 'Teacher2 Class',
            'class_code' => 'T2C-' . strtoupper(\Illuminate\Support\Str::random(4)),
            'description' => 'Class owned by Teacher2',
            'teacher_id' => $teacher2->teacher->id  // Ensure this is teacher2's Teacher record ID
        ]);

        // Act as teacher1
        $this->actingAs($teacher1, 'sanctum');

        // Attempt to create an exam for teacher2's class (should fail)
        $examData = [
            'exercise_type' => 'exam',
            'class_id' => $classForTeacher2->id,  // Use teacher2's class ID
            'exam_title' => 'Unauthorized Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'exam_end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 1,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/questions', $examData);

        // Assert unauthorized (403)
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
                'remark' => 'You can only create exams for your own classes'
            ]);

        // Ensure no exam was created
        $this->assertDatabaseMissing('exams', [
            'title' => 'Unauthorized Exam'
        ]);
    }
}
