<?php

namespace App\Repositories\Interfaces;

interface TeacherRepositoryInterface
{
    public function findByUserId($userId);
    public function getClasses($teacherId);
    public function getClassById($teacherId, $classId);
    public function getStudentsByClass($classId);
    public function getStudentProgress($studentId);
    public function getClassAnalytics($classId);
    public function getExamsByClass($classId);
    public function createExam($teacherId, array $data);
    public function updateExam($examId, array $data);
    public function deleteExam($examId);
    public function getTeacherAnalytics($teacherId);
    public function getStudentGrades($classId);
}
