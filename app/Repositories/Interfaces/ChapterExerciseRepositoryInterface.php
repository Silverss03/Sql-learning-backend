<?php

namespace App\Repositories\Interfaces;

interface ChapterExerciseRepositoryInterface
{
    public function create(array $data);
    public function findById($id);
    public function update($id, array $data);
    public function delete($id);
    public function getWithQuestions($id);
    public function submitExercise($studentId, $chapterExerciseId, $score);
    public function getAllExercises();
}
