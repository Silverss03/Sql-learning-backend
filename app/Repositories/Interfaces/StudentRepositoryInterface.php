<?php

namespace App\Repositories\Interfaces;

interface StudentRepositoryInterface
{
    public function findByUserId($userId);
    public function getAverageScore($studentId);
    public function getOverallProgress($studentId);
    public function getTopicsProgress($studentId);
    public function getLessonExerciseHistory($studentId);
    public function getChapterExerciseHistory($studentId);
    public function getExamHistory($studentId);
}
