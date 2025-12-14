<?php

namespace App\Repositories\Implementations;

use App\Models\ExamAuditLog;
use App\Repositories\Interfaces\ExamAuditLogRepositoryInterface;

class ExamAuditLogRepository implements ExamAuditLogRepositoryInterface
{
    public function create(array $data)
    {
        return ExamAuditLog::create($data);
    }

    public function getByExam($examId)
    {
        return ExamAuditLog::where('exam_id', $examId)
            ->with(['student.user', 'exam'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'student_name' => $log->student->user->name ?? 'N/A',
                    'student_email' => $log->student->user->email ?? 'N/A',
                    'event_type' => $log->event_type,
                    'session_token' => $log->session_token,
                    'created_at' => $log->created_at,
                ];
            });
    }

    public function getByStudent($studentId)
    {
        return ExamAuditLog::where('student_id', $studentId)
            ->with(['exam'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'exam_title' => $log->exam->title ?? 'N/A',
                    'event_type' => $log->event_type,
                    'session_token' => $log->session_token,
                    'created_at' => $log->created_at,
                ];
            });
    }

    public function getBySession($sessionToken)
    {
        return ExamAuditLog::where('session_token', $sessionToken)
            ->with(['student.user', 'exam'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function countTabSwitches($sessionToken)
    {
        return ExamAuditLog::where('session_token', $sessionToken)
            ->where('event_type', 'tab_switch')
            ->count();
    }
}
