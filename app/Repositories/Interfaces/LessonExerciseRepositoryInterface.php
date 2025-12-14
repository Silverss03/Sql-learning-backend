<?php

namespace App\Repositories\Interfaces;

interface LessonExerciseRepositoryInterface
{
    // Basic CRUD Operations
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findById($id);
    public function getAll();
    
    // Lesson Exercise Specific Operations
    public function getByLesson($lessonId);
    public function getExerciseWithQuestions($exerciseId);
    public function activate($exerciseId);
    public function deactivate($exerciseId);
    
    // Student Submissions
    public function submitExercise($studentId, $exerciseId, $score);
    public function getStudentSubmission($studentId, $exerciseId);
    public function getSubmissionHistory($studentId, $exerciseId);
    public function hasStudentCompleted($studentId, $exerciseId);
    
    // Statistics & Analytics
    public function getExerciseStatistics($exerciseId);
    public function getStudentProgressByExercise($studentId, $exerciseId);
    public function getAverageScore($exerciseId);
    public function getCompletionRate($exerciseId);
}
