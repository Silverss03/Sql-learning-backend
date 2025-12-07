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

class QuestionDeletionTest extends TestCase
{
    use CreatesUsers;    /** @test */
    public function teacher_can_delete_question()
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
            'description' => 'Test',
            'answer_A' => 'A',
            'answer_B' => 'B',
            'answer_C' => 'C',
            'answer_D' => 'D',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);

        $this->assertDatabaseMissing('questions', [
            'id' => $question->id
        ]);
    }    /** @test */
    public function deleting_question_cascades_to_multiple_choice_data()
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

        $mcq = MultipleChoiceQuestion::create([
            'question_id' => $question->id,
            'description' => 'Test',
            'answer_A' => 'A',
            'answer_B' => 'B',
            'answer_C' => 'C',
            'answer_D' => 'D',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/questions/{$question->id}");        $response->assertStatus(200);

        $this->assertDatabaseMissing('multiple_choices_questions', [
            'id' => $mcq->id,
            'question_id' => $question->id
        ]);
    }

    /** @test */
    public function deleting_question_cascades_to_sql_question_data()
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
            'question_title' => 'Write a query',
            'is_active' => true
        ]);        $sqlQuestion = InteractiveSqlQuestion::create([
            'question_id' => $question->id,
            'interaction_type' => 'drag_drop',
            'question_data' => json_encode(['schema' => 'test']),
            'solution_data' => json_encode(['query' => 'SELECT 1'])
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('interactive_sql_questions', [
            'id' => $sqlQuestion->id,
            'question_id' => $question->id
        ]);
    }    /** @test */
    public function student_cannot_delete_questions()
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

        $this->actingAs($student, 'sanctum');

        $response = $this->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

        // Verify question still exists
        $this->assertDatabaseHas('questions', [
            'id' => $question->id
        ]);
    }

    /** @test */
    public function returns_404_when_deleting_nonexistent_question()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->deleteJson("/api/questions/99999");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Question not found'
            ]);
    }    /** @test */
    public function unauthenticated_user_cannot_delete_questions()
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

        $response = $this->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(401);
    }    /** @test */
    public function can_delete_multiple_questions()
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

        $question1 = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'multiple_choice',
            'order_index' => 0,
            'question_title' => 'Question 1',
            'is_active' => true
        ]);

        MultipleChoiceQuestion::create([
            'question_id' => $question1->id,
            'description' => 'Test 1',
            'answer_A' => 'A',
            'answer_B' => 'B',
            'answer_C' => 'C',
            'answer_D' => 'D',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $question2 = Question::create([
            'lesson_exercise_id' => $exercise->id,
            'question_type' => 'sql',
            'order_index' => 1,
            'question_title' => 'Question 2',
            'is_active' => true
        ]);        InteractiveSqlQuestion::create([
            'question_id' => $question2->id,
            'interaction_type' => 'fill_blanks',
            'question_data' => json_encode(['schema' => 'test']),
            'solution_data' => json_encode(['query' => 'SELECT 1'])
        ]);

        $this->actingAs($teacher, 'sanctum');

        // Delete first question
        $response1 = $this->deleteJson("/api/questions/{$question1->id}");
        $response1->assertStatus(200);

        // Delete second question
        $response2 = $this->deleteJson("/api/questions/{$question2->id}");
        $response2->assertStatus(200);

        $this->assertDatabaseMissing('questions', ['id' => $question1->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question2->id]);
        $this->assertEquals(0, Question::count());
    }  
}
