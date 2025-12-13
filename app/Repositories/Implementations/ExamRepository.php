<?php

namespace App\Repositories\Implementations;

use App\Models\Exam;
use App\Models\StudentExamProgress;
use App\Models\ExamAuditLog;
use App\Repositories\Interfaces\ExamRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ExamRepository implements ExamRepositoryInterface
{
    public function findById($id)
    {
        return Exam::find($id);
    }

    public function getFutureExamsByClass($classId)
    {
        return Exam::where('class_id', $classId)
            ->where('end_time', '>', now())
            ->orderBy('start_time')
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'topic_id' => $exam->topic_id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'duration_minutes' => $exam->duration_minutes,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,
                    'is_active' => $exam->is_active,
                    'created_by' => $exam->created_by,
                    'created_at' => $exam->created_at,
                    'updated_at' => $exam->updated_at,
                    'is_completed' => null,
                    'score' => null,
                    'submitted_at' => null,
                ];
            });
    }

    public function getExamHistoryByStudent($studentId)
    {
        $progressRecords = StudentExamProgress::where('student_id', $studentId)
            ->where('is_completed', true)
            ->with('exam')
            ->orderBy('submitted_at', 'desc')
            ->get();

        return $progressRecords->map(function ($record) {
            return [
                'exam_id' => $record->exam->id,
                'title' => $record->exam->title,
                'description' => $record->exam->description,
                'duration_minutes' => $record->exam->duration_minutes,
                'start_time' => $record->exam->start_time,
                'end_time' => $record->exam->end_time,
                'score' => $record->score,
                'submitted_at' => $record->submitted_at,
            ];
        });
    }

    public function getExamWithQuestions($examId)
    {
        $exam = $this->findById($examId);
        
        if (!$exam) {
            return null;
        }

        $questions = $exam->questions()->where('is_active', true)->orderBy('order_index')->get();

        $multipleChoiceQuestions = $questions->map(function ($question) {
            return $question->multipleChoice;
        })->filter();

        $sqlQuestions = $questions->map(function ($question) {
            return $question->interactiveSqlQuestion;
        })->filter();

        return [
            'exam' => $exam,
            'questions' => [
                'multipleChoice' => $multipleChoiceQuestions,
                'sqlQuestions' => $sqlQuestions
            ]
        ];
    }

    public function startExam($examId, $studentId, $deviceFingerprint)
    {
        $sessionToken = Str::random(32);

        $progress = StudentExamProgress::create([
            'student_id' => $studentId,
            'exam_id' => $examId,
            'is_completed' => false,
            'session_token' => $sessionToken,
            'device_fingerprint' => $deviceFingerprint,
            'started_at' => now(),
        ]);

        $exam = $this->findById($examId);
        $questions = $exam->questions()->where('is_active', true)->orderBy('order_index')->get();

        $multipleChoiceQuestions = $questions->map(function ($question) {
            return $question->multipleChoice;
        })->filter();

        $sqlQuestions = $questions->map(function ($question) {
            return $question->interactiveSqlQuestion;
        })->filter();

        return [
            'session_token' => $sessionToken,
            'exam' => $exam,
            'questions' => [
                'multipleChoice' => $multipleChoiceQuestions,
                'sqlQuestions' => $sqlQuestions
            ]
        ];
    }

    public function submitExam($examId, $studentId, $sessionToken, $deviceFingerprint, $score)
    {
        $progress = StudentExamProgress::where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->where('session_token', $sessionToken)
            ->where('device_fingerprint', $deviceFingerprint)
            ->first();

        if (!$progress || $progress->is_completed) {
            return null;
        }

        $violationCount = ExamAuditLog::where('session_token', $sessionToken)->count();

        DB::beginTransaction();

        if($violationCount > 0) {
            $progress->update([
                'is_completed' => true,
                'score' => 0,
                'submitted_at' => now(),
            ]);

            DB::commit();
            
            return [
                'has_violations' => true,
                'progress' => $progress
            ];
        }

        $progress->update([
            'is_completed' => true,
            'score' => $score,
            'submitted_at' => now(),
        ]);

        DB::commit();

        return [
            'has_violations' => false,
            'progress' => $progress
        ];
    }

    public function updateExamStatus($examId, $isActive)
    {
        $exam = $this->findById($examId);
        
        if (!$exam) {
            return null;
        }

        $exam->update(['is_active' => $isActive]);
        
        return $exam;
    }
}
