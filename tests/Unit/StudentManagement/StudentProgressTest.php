<?php

namespace Tests\Unit\StudentProgress;

use Tests\TestCase;
use Tests\Traits\CreatesUsers;
use App\Models\User;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\LessonExercise;
use App\Models\ChapterExercise;
use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\StudentLessonProgress;
use App\Models\StudentChapterExerciseProgress;
use App\Models\StudentExamProgress;

class StudentProgressTest extends TestCase
{
    use CreatesUsers;

    /** @test */
    public function student_can_get_average_score()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;

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

        $this->actingAs($student, 'sanctum');

        // Create lesson progress records
        $lessonExercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 85,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 90,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        $response = $this->getJson("/api/students/average-score?user_id={$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['student_id', 'average_score'],
                'message',
                'success',
                'remark'
            ])
            ->assertJson([
                'success' => true
            ]);

        $this->assertEquals(90, $response->json('data.average_score'));
    }

    /** @test */
    public function average_score_requires_user_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/students/average-score');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function average_score_validates_user_exists()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/students/average-score?user_id=99999');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function average_score_returns_404_for_non_student_user()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $teacher = $this->createTeacher(['email' => 'teacher2@test.com']);

        $response = $this->getJson("/api/students/average-score?user_id={$teacher->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found for the given user ID'
            ]);
    }

    /** @test */
    public function average_score_includes_chapter_exercises()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $chapterExercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        StudentChapterExerciseProgress::create([
            'student_id' => $studentModel->id,
            'chapter_exercise_id' => $chapterExercise->id,
            'score' => 95,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        $response = $this->getJson("/api/students/average-score?user_id={$student->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function student_can_get_overall_progress()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        // Create multiple lessons
        $lesson1 = Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'SELECT Statement',
            'order_index' => 1,
            'is_active' => true,
            'lesson_content' => 'Content about SELECT'
        ]);

        Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'WHERE Clause',
            'order_index' => 2,
            'is_active' => true,
            'lesson_content' => 'Content about WHERE'
        ]);

        Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'JOIN Statement',
            'order_index' => 3,
            'is_active' => true,
            'lesson_content' => 'Content about JOIN'
        ]);

        $this->actingAs($student, 'sanctum');

        // Student completed 1 lesson
        $lessonExercise = LessonExercise::create([
            'lesson_id' => $lesson1->id,
            'is_active' => true
        ]);

        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson1->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 85,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        $response = $this->getJson("/api/students/progress?user_id={$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_lessons', 'completed_lessons', 'progress_percentage'],
                'message',
                'success',
                'remark'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_lessons' => 3,
                    'completed_lessons' => 1
                ]
            ]);
    }

    /** @test */
    public function overall_progress_requires_user_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/students/progress');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function overall_progress_returns_404_for_non_student()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $teacher = $this->createTeacher(['email' => 'teacher2@test.com']);

        $response = $this->getJson("/api/students/progress?user_id={$teacher->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found'
            ]);
    }

    /** @test */
    public function student_can_get_topics_progress()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;

        // Create first topic with lessons
        $topic1 = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $lesson1 = Lesson::create([
            'topic_id' => $topic1->id,
            'title' => 'SELECT Statement',
            'order_index' => 1,
            'is_active' => true,
            'lesson_content' => 'Content about SELECT'
        ]);

        // Create another topic with lessons
        $topic2 = Topic::create([
            'topic_name' => 'Advanced SQL',
            'description' => 'Advanced SQL concepts',
            'order_index' => 2,
            'is_active' => true
        ]);

        Lesson::create([
            'topic_id' => $topic2->id,
            'title' => 'Subqueries',
            'order_index' => 1,
            'is_active' => true,
            'lesson_content' => 'Content about subqueries'
        ]);

        Lesson::create([
            'topic_id' => $topic2->id,
            'title' => 'Indexes',
            'order_index' => 2,
            'is_active' => true,
            'lesson_content' => 'Content about indexes'
        ]);

        $this->actingAs($student, 'sanctum');

        // Student completed 1 lesson in first topic
        $lessonExercise = LessonExercise::create([
            'lesson_id' => $lesson1->id,
            'is_active' => true
        ]);

        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson1->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 85,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        $response = $this->getJson("/api/students/topics-progress?user_id={$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'topic_id',
                        'topic_title',
                        'total_lessons',
                        'completed_lessons',
                        'progress_percentage'
                    ]
                ],
                'message',
                'success',
                'remark'
            ])
            ->assertJson([
                'success' => true
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function topics_progress_requires_user_id()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/students/topics-progress');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    /** @test */
    public function topics_progress_returns_404_for_non_student()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $teacher = $this->createTeacher(['email' => 'teacher2@test.com']);

        $response = $this->getJson("/api/students/topics-progress?user_id={$teacher->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found'
            ]);
    }

    /** @test */
    public function student_can_get_lesson_exercise_history()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;

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
            'lesson_content' => 'Content about SELECT'
        ]);

        $this->actingAs($student, 'sanctum');

        $lessonExercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        // Create multiple progress records
        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 75,
            'completed_at' => now()->subDays(2),
            'submitted_at' => now()->subDays(2)
        ]);

        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 90,
            'completed_at' => now()->subDay(),
            'submitted_at' => now()->subDay()
        ]);

        $response = $this->getJson('/api/students/lesson-exercise-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'lesson_id',
                        'lesson_title',
                        'score',
                        'completed_at'
                    ]
                ],
                'message',
                'success',
                'remark'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Lesson exercise history retrieved successfully'
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function lesson_exercise_history_requires_authentication()
    {
        $response = $this->getJson('/api/students/lesson-exercise-history');

        $response->assertStatus(401);
    }

    /** @test */
    public function lesson_exercise_history_returns_404_for_non_student()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson('/api/students/lesson-exercise-history');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found'
            ]);
    }

    /** @test */
    public function student_can_get_chapter_exercise_history()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;

        $topic = Topic::create([
            'topic_name' => 'SQL Basics',
            'description' => 'Introduction to SQL',
            'order_index' => 1,
            'is_active' => true
        ]);

        $this->actingAs($student, 'sanctum');

        $chapterExercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        // Create progress records
        StudentChapterExerciseProgress::create([
            'student_id' => $studentModel->id,
            'chapter_exercise_id' => $chapterExercise->id,
            'score' => 80,
            'completed_at' => now()->subDays(3),
            'submitted_at' => now()->subDays(3)
        ]);

        StudentChapterExerciseProgress::create([
            'student_id' => $studentModel->id,
            'chapter_exercise_id' => $chapterExercise->id,
            'score' => 95,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        $response = $this->getJson('/api/students/chapter-exercise-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'chapter_exercise_id',
                        'chapter_exercise_title',
                        'score',
                        'completed_at'
                    ]
                ],
                'message',
                'success',
                'remark'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Chapter exercise history retrieved successfully'
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function chapter_exercise_history_requires_authentication()
    {
        $response = $this->getJson('/api/students/chapter-exercise-history');

        $response->assertStatus(401);
    }

    /** @test */
    public function chapter_exercise_history_returns_404_for_non_student()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson('/api/students/chapter-exercise-history');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found'
            ]);
    }

    /** @test */
    public function student_can_get_exam_history()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $teacher = $this->createTeacher(['email' => 'exam-teacher@test.com']);
        
        $class = ClassModel::where('teacher_id', $teacher->teacher->id)->first();
        if (!$class) {
            $class = ClassModel::create([
                'class_name' => 'Exam Class',
                'class_code' => 'EXAM101',
                'teacher_id' => $teacher->teacher->id,
                'is_active' => true
            ]);
        }

        $this->actingAs($student, 'sanctum');

        $exam = Exam::create([
            'title' => 'Midterm Exam',
            'description' => 'SQL Fundamentals',
            'duration_minutes' => 60,
            'start_time' => now()->subDay(),
            'end_time' => now()->addDay(),
            'class_id' => $class->id,
            'created_by' => $teacher->id,
            'is_active' => true
        ]);

        // Create exam progress records
        StudentExamProgress::create([
            'student_id' => $studentModel->id,
            'exam_id' => $exam->id,
            'is_completed' => true,
            'score' => 88,
            'submitted_at' => now(),
            'session_token' => 'test-token'
        ]);

        $response = $this->getJson('/api/students/exam-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'exam_id',
                        'exam_title',
                        'is_completed',
                        'score',
                        'submitted_at'
                    ]
                ],
                'message',
                'success',
                'remark'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Exam history retrieved successfully'
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Midterm Exam', $response->json('data.0.exam_title'));
    }

    /** @test */
    public function exam_history_requires_authentication()
    {
        $response = $this->getJson('/api/students/exam-history');

        $response->assertStatus(401);
    }

    /** @test */
    public function exam_history_returns_404_for_non_student()
    {
        $teacher = $this->createTeacher();
        $this->actingAs($teacher, 'sanctum');

        $response = $this->getJson('/api/students/exam-history');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found'
            ]);
    }

    /** @test */
    public function empty_lesson_exercise_history_returns_successfully()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/students/lesson-exercise-history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => []
            ]);
    }

    /** @test */
    public function empty_chapter_exercise_history_returns_successfully()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/students/chapter-exercise-history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => []
            ]);
    }

    /** @test */
    public function empty_exam_history_returns_successfully()
    {
        $student = $this->createStudent();
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/students/exam-history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => []
            ]);
    }

    /** @test */
    public function average_score_calculates_correctly_with_multiple_exercises()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;
        $teacher = $this->createTeacher(['email' => 'score-teacher@test.com']);

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
            'lesson_content' => 'Content about SELECT'
        ]);

        $this->actingAs($student, 'sanctum');

        $lessonExercise = LessonExercise::create([
            'lesson_id' => $lesson->id,
            'is_active' => true
        ]);

        $chapterExercise = ChapterExercise::create([
            'topic_id' => $topic->id,
            'is_active' => true
        ]);

        $class = ClassModel::where('teacher_id', $teacher->teacher->id)->first();
        if (!$class) {
            $class = ClassModel::create([
                'class_name' => 'Score Class',
                'class_code' => 'SCORE101',
                'teacher_id' => $teacher->teacher->id,
                'is_active' => true
            ]);
        }

        $exam = Exam::create([
            'title' => 'Test Exam',
            'duration_minutes' => 60,
            'start_time' => now()->subDay(),
            'end_time' => now()->addDay(),
            'class_id' => $class->id,
            'created_by' => $teacher->id,
            'is_active' => true
        ]);

        // Lesson progress - max score is 90
        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 80,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        StudentLessonProgress::create([
            'student_id' => $studentModel->id,
            'lesson_id' => $lesson->id,
            'lesson_exercise_id' => $lessonExercise->id,
            'score' => 90,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        // Chapter progress - max score is 85
        StudentChapterExerciseProgress::create([
            'student_id' => $studentModel->id,
            'chapter_exercise_id' => $chapterExercise->id,
            'score' => 85,
            'completed_at' => now(),
            'submitted_at' => now()
        ]);

        // Exam progress - score is 95
        StudentExamProgress::create([
            'student_id' => $studentModel->id,
            'exam_id' => $exam->id,
            'is_completed' => true,
            'score' => 95,
            'submitted_at' => now(),
            'session_token' => 'test-token'
        ]);

        $response = $this->getJson("/api/students/average-score?user_id={$student->id}");

        $response->assertStatus(200);
        
        // Average should be (90 + 85 + 95) / 3 = 90
        $this->assertEquals(90, $response->json('data.average_score'));
    }

    /** @test */
    public function topics_progress_only_includes_active_topics()
    {
        $student = $this->createStudent();
        $studentModel = $student->student;

        // Create active topic
        $activeTopic = Topic::create([
            'topic_name' => 'Active SQL',
            'description' => 'Active topic',
            'order_index' => 1,
            'is_active' => true
        ]);

        Lesson::create([
            'topic_id' => $activeTopic->id,
            'title' => 'SELECT Statement',
            'order_index' => 1,
            'is_active' => true,
            'lesson_content' => 'Content about SELECT'
        ]);

        // Create inactive topic
        $inactiveTopic = Topic::create([
            'topic_name' => 'Inactive SQL',
            'description' => 'Inactive topic',
            'order_index' => 2,
            'is_active' => false
        ]);

        Lesson::create([
            'topic_id' => $inactiveTopic->id,
            'title' => 'DELETE Statement',
            'order_index' => 1,
            'is_active' => true,
            'lesson_content' => 'Content about DELETE'
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/students/topics-progress?user_id={$student->id}");

        $response->assertStatus(200);

        // Should only include active topic
        $this->assertCount(1, $response->json('data'));
    }
}
