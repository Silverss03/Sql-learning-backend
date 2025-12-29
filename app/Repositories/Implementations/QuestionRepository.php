<?php

namespace App\Repositories\Implementations;

use App\Models\Question;
use App\Models\LessonExercise;
use App\Models\ChapterExercise;
use App\Models\Exam;
use App\Models\MultipleChoiceQuestion;
use App\Models\InteractiveSqlQuestion;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Models\ClassModel;
use App\Events\ExamCreated;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function createExerciseWithQuestions(array $data)
    {
        DB::beginTransaction();

        try {
            // Create exercise based on type
            $exercise = $this->createExercise($data);

            $questionsToInsert = [];
            $mcQuestionsToInsert = [];
            $sqlQuestionsToInsert = [];

            $now = now();
            $exerciseIdField = $this->getExerciseIdField($data['exercise_type']);

            foreach ($data['questions'] as $index => $questionData) {
                $questionsToInsert[] = [
                    $exerciseIdField => $exercise->id,
                    'question_type' => $questionData['type'],
                    'order_index' => $questionData['order_index'],
                    'question_title' => $questionData['title'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Question::insert($questionsToInsert);

            $insertedQuestions = Question::where($exerciseIdField, $exercise->id)
                ->orderBy('id')
                ->get();

            foreach ($data['questions'] as $index => $questionData) {
                $questionId = $insertedQuestions[$index]->id;

                if ($questionData['type'] === 'multiple_choice') {
                    $mcQuestionsToInsert[] = [
                        'question_id' => $questionId,
                        'description' => $questionData['details']['description'],
                        'answer_A' => $questionData['details']['answer_A'],
                        'answer_B' => $questionData['details']['answer_B'],
                        'answer_C' => $questionData['details']['answer_C'],
                        'answer_D' => $questionData['details']['answer_D'],
                        'correct_answer' => $questionData['details']['correct_answer'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } else {
                    $sqlQuestionsToInsert[] = [
                        'question_id' => $questionId,
                        'interaction_type' => $questionData['details']['interaction_type'],
                        'question_data' => json_encode($questionData['details']['question_data']),
                        'solution_data' => json_encode($questionData['details']['solution_data']),
                        'description' => $questionData['details']['description'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($mcQuestionsToInsert)) {
                MultipleChoiceQuestion::insert($mcQuestionsToInsert);
            }

            if (!empty($sqlQuestionsToInsert)) {
                InteractiveSqlQuestion::insert($sqlQuestionsToInsert);
            }

            DB::commit();

            $createdQuestions = Question::where($exerciseIdField, $exercise->id)
                ->with(['multipleChoice', 'interactiveSqlQuestion'])
                ->orderBy('id')
                ->get();

            return [
                'exercise' => $exercise,
                'questions' => $createdQuestions
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createExercise(array $data)
    {
        switch ($data['exercise_type']) {
            case 'lesson':
                return LessonExercise::create([
                    'lesson_id' => $data['parent_id'],
                    'is_active' => true,
                ]);

            case 'chapter':
                return ChapterExercise::create([
                    'topic_id' => $data['parent_id'],
                    'is_active' => true,
                ]);

            case 'exam':
                $class = ClassModel::where('id', $data['class_id'])
                    ->where('teacher_id', $data['teacher_id'])
                    ->first();

                if (!$class) {
                    throw new \Exception('Unauthorized: You can only create exams for your own classes');
                }

                $exam = Exam::create([
                    'title' => $data['exam_title'] ?? 'Untitled Exam',
                    'description' => $data['exam_description'] ?? null,
                    'duration_minutes' => $data['exam_duration_minutes'] ?? 60,
                    'start_time' => $data['exam_start_time'] ?? now()->addMinutes(10),
                    'end_time' => $data['exam_end_time'] ?? now()->addHours(2),
                    'is_active' => false,
                    'created_by' => $data['created_by'],
                    'class_id' => $data['class_id'],
                ]);

                event(new ExamCreated($exam));

                return $exam;

            default:
                throw new \Exception('Invalid exercise type');
        }
    }

    private function getExerciseIdField($exerciseType)
    {
        switch ($exerciseType) {
            case 'lesson':
                return 'lesson_exercise_id';
            case 'chapter':
                return 'chapter_exercise_id';
            case 'exam':
                return 'exam_id';
            default:
                throw new \Exception('Invalid exercise type');
        }
    }

    public function findById($id)
    {
        return Question::with(['multipleChoice', 'interactiveSqlQuestion'])->find($id);
    }

    public function update($id, array $data)
    {
        $question = $this->findById($id);
        
        if (!$question) {
            return null;
        }

        DB::beginTransaction();

        try {
            // Update base question
            $question->update([
                'order_index' => $data['order_index'] ?? $question->order_index,
                'question_title' => $data['title'] ?? $question->question_title,
                'is_active' => $data['is_active'] ?? $question->is_active,
            ]);

            // Update specific question type
            if (isset($data['details'])) {
                if ($question->question_type === 'multiple_choice' && $question->multipleChoice) {
                    $question->multipleChoice->update($data['details']);
                } elseif ($question->question_type === 'sql' && $question->interactiveSqlQuestion) {
                    $question->interactiveSqlQuestion->update($data['details']);
                }
            }

            DB::commit();

            return $question->fresh(['multipleChoice', 'interactiveSqlQuestion']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        $question = $this->findById($id);
        
        if (!$question) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Delete related records
            if ($question->multipleChoice) {
                $question->multipleChoice->delete();
            }
            
            if ($question->interactiveSqlQuestion) {
                $question->interactiveSqlQuestion->delete();
            }

            $question->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
