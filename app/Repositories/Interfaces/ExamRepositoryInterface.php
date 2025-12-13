<?php

namespace App\Repositories\Interfaces;

interface ExamRepositoryInterface
{
    public function findById($id);
    public function getFutureExamsByClass($classId);
    public function getExamWithQuestions($examId);
    public function startExam($examId, $studentId, $deviceFingerprint);
    public function submitExam($examId, $studentId, $sessionToken, $deviceFingerprint, $score);
    public function updateExamStatus($examId, $isActive);
    public function getExamHistoryByStudent($studentId);
}
