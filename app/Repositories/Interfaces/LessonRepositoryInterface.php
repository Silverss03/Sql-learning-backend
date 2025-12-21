<?php

namespace App\Repositories\Interfaces;

interface LessonRepositoryInterface
{
    public function findActiveById($id);
    public function getQuestionsByLesson($lessonId);
    public function getExerciseByLesson($lessonId);
    public function submitExercise($studentId, $lessonExerciseId, $score);
    
    // List and detail methods
    public function getAll($filters = []);
    public function getByIdWithDetails($id);
    
    // Admin CRUD operations
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findById($id);
}
