<?php

namespace Tests\Unit\QuestionManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\LessonExercise;
use App\Models\ChapterExercise;
use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\Question;
use App\Models\MultipleChoiceQuestion;
use App\Models\InteractiveSqlQuestion;

class QuestionCreationTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function teacher_can_create_lesson_exercise_with_multiple_choice_question()
    {
        $teacher = $this->createTeacher();
        
        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'SELECT Statement',
            'order_index' => 1,
            'is_active' => true,
            'lesson_content' => 'Content about SELECT statement'
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'lesson',
            'parent_id' => $lesson->id,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
                    'title' => 'What does SELECT do?',
                    'details' => [
                        'description' => 'Choose the correct answer about SELECT statement',
                        'answer_A' => 'Retrieves data from database',
                        'answer_B' => 'Deletes data',
                        'answer_C' => 'Updates data',
                        'answer_D' => 'Creates table',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Exercise and questions created successfully'
            ]);

        $this->assertDatabaseHas('lesson_exercises', [
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('questions', [
            'question_type' => 'multiple_choice',
            'question_title' => 'What does SELECT do?',
            'order_index' => 0,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('multiple_choices_questions', [
            'description' => 'Choose the correct answer about SELECT statement',
            'answer_A' => 'Retrieves data from database',
            'correct_answer' => 'A'
        ]);
    }

    /** @test */
    public function teacher_can_create_lesson_exercise_with_sql_question()
    {
        $teacher = $this->createTeacher();
        
        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'SELECT Statement',
            'order_index' => 1,
            'is_active' => true,
            'lesson_content' => 'Content about SELECT statement'
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'lesson',
            'parent_id' => $lesson->id,
            'questions' => [
                [
                    'type' => 'sql',
                    'order_index' => 0,
                    'title' => 'Write a SELECT query',
                    'details' => [
                        'interaction_type' => 'drag_drop',
                        'question_data' => json_encode(['schema' => 'students', 'tables' => ['users']]),
                        'solution_data' => json_encode(['query' => 'SELECT * FROM users']),
                        'description' => 'Write a query to select all users'
                    ]
                ]
            ]
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Exercise and questions created successfully'
            ]);

        $this->assertDatabaseHas('interactive_sql_questions', [
            'interaction_type' => 'drag_drop',
            'description' => 'Write a query to select all users'
        ]);
    }

    /** @test */
    public function teacher_can_create_chapter_exercise_with_questions()
    {
        $teacher = $this->createTeacher();
        
        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'chapter',
            'parent_id' => $topic->id,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
                    'title' => 'Chapter Review Question',
                    'details' => [
                        'description' => 'Test question',
                        'answer_A' => 'Option A',
                        'answer_B' => 'Option B',
                        'answer_C' => 'Option C',
                        'answer_D' => 'Option D',
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('chapter_exercises', [
            'topic_id' => $topic->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function teacher_can_create_exam_with_questions()
    {
        $teacher = $this->createTeacher();
        $teacherModel = Teacher::where('user_id', $teacher->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS101',
            'class_name' => 'Test Class',
            'teacher_id' => $teacherModel->id,
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'exam',
            'class_id' => $class->id,
            'exam_title' => 'Midterm Exam',
            'exam_description' => 'SQL Fundamentals Exam',
            'exam_duration_minutes' => 90,
            'exam_start_time' => now()->addDays(1)->toDateTimeString(),
            'exam_end_time' => now()->addDays(2)->toDateTimeString(),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
                    'title' => 'Exam Question 1',
                    'details' => [
                        'description' => 'Exam question description',
                        'answer_A' => 'Answer A',
                        'answer_B' => 'Answer B',
                        'answer_C' => 'Answer C',
                        'answer_D' => 'Answer D',
                        'correct_answer' => 'B'
                    ]
                ]
            ]
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('exams', [
            'title' => 'Midterm Exam',
            'duration_minutes' => 90,
            'class_id' => $class->id,
            'is_active' => false // Exams are created inactive by default
        ]);
    }

    /** @test */
    public function exercise_type_is_required()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/questions', [
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
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
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function questions_array_is_required()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'chapter',
            'parent_id' => $topic->id
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function multiple_choice_question_requires_all_answer_options()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'chapter',
            'parent_id' => $topic->id,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        // Missing answer_C and answer_D
                        'correct_answer' => 'A'
                    ]
                ]
            ]
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function correct_answer_must_be_valid_option()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'chapter',
            'parent_id' => $topic->id,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
                    'title' => 'Test Question',
                    'details' => [
                        'description' => 'Test',
                        'answer_A' => 'A',
                        'answer_B' => 'B',
                        'answer_C' => 'C',
                        'answer_D' => 'D',
                        'correct_answer' => 'E' // Invalid
                    ]
                ]
            ]
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function sql_question_requires_interaction_type()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'chapter',
            'parent_id' => $topic->id,
            'questions' => [
                [
                    'type' => 'sql',
                    'order_index' => 0,
                    'title' => 'Write a query',
                    'details' => [
                        // Missing interaction_type
                        'question_data' => json_encode(['schema' => 'test']),
                        'solution_data' => json_encode(['query' => 'SELECT 1'])
                    ]
                ]
            ]
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function student_cannot_create_questions()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'chapter',
            'parent_id' => $topic->id,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
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
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function teacher_can_only_create_exams_for_own_classes()
    {
        $teacher1 = $this->createTeacher();
        $teacher2 = $this->createTeacher(['email' => 'teacher2@test.com']);
        
        $teacher2Model = Teacher::where('user_id', $teacher2->id)->first();
        
        $class = ClassModel::create([
            'class_code' => 'CS101',
            'class_name' => 'Teacher 2 Class',
            'teacher_id' => $teacher2Model->id,
            'is_active' => true
        ]);

        $this->actingAs($teacher1, 'sanctum');

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'exam',
            'class_id' => $class->id,
            'exam_title' => 'Unauthorized Exam',
            'exam_duration_minutes' => 60,
            'exam_start_time' => now()->addDays(1)->toDateTimeString(),
            'exam_end_time' => now()->addDays(2)->toDateTimeString(),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
                    'title' => 'Question',
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
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'remark' => 'You can only create exams for your own classes'
            ]);
    }

    /** @test */
    public function can_create_multiple_questions_in_one_request()
    {
        $teacher = $this->createTeacher();
        
        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->postJson('/api/questions', [
            'exercise_type' => 'chapter',
            'parent_id' => $topic->id,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'order_index' => 0,
                    'title' => 'Question 1',
                    'details' => [
                        'description' => 'Test 1',
                        'answer_A' => 'A1',
                        'answer_B' => 'B1',
                        'answer_C' => 'C1',
                        'answer_D' => 'D1',
                        'correct_answer' => 'A'
                    ]
                ],
                [
                    'type' => 'sql',
                    'order_index' => 1,
                    'title' => 'Question 2',
                    'details' => [
                        'interaction_type' => 'drag_drop',
                        'question_data' => json_encode(['schema' => 'test']),
                        'solution_data' => json_encode(['query' => 'SELECT 1'])
                    ]
                ],
                [
                    'type' => 'multiple_choice',
                    'order_index' => 2,
                    'title' => 'Question 3',
                    'details' => [
                        'description' => 'Test 3',
                        'answer_A' => 'A3',
                        'answer_B' => 'B3',
                        'answer_C' => 'C3',
                        'answer_D' => 'D3',
                        'correct_answer' => 'C'
                    ]
                ]
            ]
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'remark' => 'New exercise created with 3 questions'
            ]);

        $this->assertEquals(3, Question::count());
        $this->assertEquals(2, MultipleChoiceQuestion::count());
        $this->assertEquals(1, InteractiveSqlQuestion::count());
    }
}
