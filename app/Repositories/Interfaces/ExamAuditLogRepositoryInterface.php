<?php

namespace App\Repositories\Interfaces;

interface ExamAuditLogRepositoryInterface
{
    // Create audit log
    public function create(array $data);
    
    // Get logs by exam
    public function getByExam($examId);
    
    // Get logs by student
    public function getByStudent($studentId);
    
    // Count tab switches for a session
    public function countTabSwitches($sessionToken);
}
