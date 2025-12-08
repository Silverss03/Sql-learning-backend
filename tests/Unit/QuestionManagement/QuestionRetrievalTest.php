<?php

namespace Tests\Unit\QuestionManagement;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\LessonExercise;
use App\Models\Question;
use App\Models\MultipleChoiceQuestion;
use App\Models\InteractiveSqlQuestion;

class QuestionRetrievalTest extends TestCase
{
    use CreatesUsers;    /** @test */
    public function can_retrieve_single_question_by_id()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $question = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'multiple_choice',
            'order_index' => 0,
            'question_title' => 'What is SQL?',
            'is_active' => true
        ]);

        $mcq = MultipleChoiceQuestion::create([
            'question_id' => $question->id,
            'description' => 'SQL stands for?',
            'answer_A' => 'Structured Query Language',
            'answer_B' => 'Simple Query Language',
            'answer_C' => 'Standard Question Language',
            'answer_D' => 'Sequential Query Logic',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Question retrieved successfully',
                'data' => [
                    'id' => $question->id,
                    'question_type' => 'multiple_choice',
                    'question_title' => 'What is SQL?',
                    'order_index' => 0,
                    'is_active' => true
                ]
            ]);

        $this->assertNotNull($response->json('data.multiple_choice'));
    }    /** @test */
    public function can_retrieve_sql_question_with_details()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $question = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'sql',
            'order_index' => 0,
            'question_title' => 'Write a SELECT query',
            'is_active' => true
        ]);        $sqlQuestion = InteractiveSqlQuestion::create([
            'question_id' => $question->id,
            'interaction_type' => 'drag_drop',
            'question_data' => json_encode(['schema' => 'students', 'tables' => ['users']]),
            'solution_data' => json_encode(['query' => 'SELECT * FROM users']),
            'description' => 'Select all users from the database'
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'question_type' => 'sql'
                ]
            ]);

        $this->assertNotNull($response->json('data.interactive_sql_question'));
    }

    /** @test */
    public function returns_404_for_nonexistent_question()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson("/api/questions/99999");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Question not found'
            ]);
    }    /** @test */
    public function response_includes_type_specific_data()
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $question = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'multiple_choice',
            'order_index' => 0,
            'question_title' => 'Test Question',
            'is_active' => true
        ]);

        MultipleChoiceQuestion::create([
            'question_id' => $question->id,
            'description' => 'Description',
            'answer_A' => 'Option A',
            'answer_B' => 'Option B',
            'answer_C' => 'Option C',
            'answer_D' => 'Option D',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson("/api/questions/{$question->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('multiple_choice', $data);
        $this->assertEquals('Description', $data['multiple_choice']['description']);
        $this->assertEquals('Option A', $data['multiple_choice']['answer_A']);
        $this->assertEquals('A', $data['multiple_choice']['correct_answer']);
    }    /** @test */
    public function unauthenticated_user_cannot_retrieve_questions()
    {
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $question = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'multiple_choice',
            'order_index' => 0,
            'question_title' => 'Test Question',
            'is_active' => true
        ]);

        $response = $this->getJson("/api/questions/{$question->id}");

        $response->assertStatus(401);
    }    /** @test */
    public function student_can_retrieve_questions()
    {
        $student = $this->createStudent();
        
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

        $exercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $question = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'multiple_choice',
            'order_index' => 0,
            'question_title' => 'Test Question',
            'is_active' => true
        ]);

        MultipleChoiceQuestion::create([
            'question_id' => $question->id,
            'description' => 'Description',
            'answer_A' => 'Option A',
            'answer_B' => 'Option B',
            'answer_C' => 'Option C',
            'answer_D' => 'Option D',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Question retrieved successfully'
            ]);
    }
}
