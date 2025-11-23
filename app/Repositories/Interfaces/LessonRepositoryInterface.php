<?php

namespace App\Repositories\Interfaces;

interface LessonRepositoryInterface
{
    public function findActiveById($id);
    public function getQuestionsByLesson($lessonId);
    public function getExerciseByLesson($lessonId);
    public function submitExercise($studentId, $lessonExerciseId, $score);
}
