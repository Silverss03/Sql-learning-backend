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

class QuestionUpdateTest extends TestCase
{
    use CreatesUsers;    /** @test */
    public function teacher_can_update_question_title()
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
            'question_title' => 'Original Title',
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

        $response = $this->putJson("/api/questions/{$question->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Question updated successfully'
            ]);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_title' => 'Updated Title'
        ]);
    }    /** @test */
    public function teacher_can_update_question_order_index()
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

        $response = $this->putJson("/api/questions/{$question->id}", [
            'order_index' => 5
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'order_index' => 5
        ]);
    }    /** @test */
    public function teacher_can_update_multiple_choice_answer_options()
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
            'description' => 'Original description',
            'answer_A' => 'Original A',
            'answer_B' => 'Original B',
            'answer_C' => 'Original C',
            'answer_D' => 'Original D',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/questions/{$question->id}", [
            'details' => [
                'description' => 'Updated description',
                'answer_A' => 'New A',
                'answer_B' => 'New B',
                'answer_C' => 'New C',
                'answer_D' => 'New D'
            ]
        ]);

        $response->assertStatus(200);        $this->assertDatabaseHas('multiple_choices_questions', [
            'question_id' => $question->id,
            'description' => 'Updated description',
            'answer_A' => 'New A',
            'answer_B' => 'New B'
        ]);
    }

    /** @test */
    public function teacher_can_update_correct_answer()
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

        $response = $this->putJson("/api/questions/{$question->id}", [
            'details' => [
                'correct_answer' => 'C'
            ]
        ]);        $response->assertStatus(200);

        $this->assertDatabaseHas('multiple_choices_questions', [
            'question_id' => $question->id,
            'correct_answer' => 'C'
        ]);
    }

    /** @test */
    public function teacher_can_update_sql_question_details()
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
        ]);        InteractiveSqlQuestion::create([
            'question_id' => $question->id,
            'interaction_type' => 'drag_drop',
            'question_data' => json_encode(['schema' => 'old']),
            'solution_data' => json_encode(['query' => 'SELECT 1']),
            'description' => 'Old description'
        ]);

        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/questions/{$question->id}", [
            'details' => [
                'description' => 'New description',
                'question_data' => json_encode(['schema' => 'new']),
                'solution_data' => json_encode(['query' => 'SELECT * FROM users'])
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('interactive_sql_questions', [
            'question_id' => $question->id,
            'description' => 'New description'
        ]);
    }    /** @test */
    public function teacher_can_deactivate_question()
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

        $response = $this->putJson("/api/questions/{$question->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'is_active' => false
        ]);
    }    /** @test */
    public function partial_updates_work_correctly()
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
            'question_title' => 'Original Title',
            'is_active' => true
        ]);

        MultipleChoiceQuestion::create([
            'question_id' => $question->id,
            'description' => 'Original description',
            'answer_A' => 'A',
            'answer_B' => 'B',
            'answer_C' => 'C',
            'answer_D' => 'D',
            'correct_answer' => 'A',
            'is_active' => true
        ]);

        $this->actingAs($teacher, 'sanctum');

        // Only update title, leave other fields unchanged
        $response = $this->putJson("/api/questions/{$question->id}", [
            'title' => 'New Title'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_title' => 'New Title',
            'order_index' => 0, // Should remain unchanged
            'is_active' => true // Should remain unchanged
        ]);
    }    /** @test */
    public function student_cannot_update_questions()
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

        $response = $this->putJson("/api/questions/{$question->id}", [
            'title' => 'Hacked Title'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function returns_404_when_updating_nonexistent_question()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->putJson("/api/questions/99999", [
            'title' => 'New Title'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Question not found'
            ]);
    }    /** @test */
    public function validates_correct_answer_format()
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

        $response = $this->putJson("/api/questions/{$question->id}", [
            'details' => [
                'correct_answer' => 'Z' // Invalid option
            ]
        ]);

        $response->assertStatus(422);
    }
}
