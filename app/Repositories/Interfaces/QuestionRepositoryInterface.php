<?php

namespace App\Repositories\Interfaces;

interface QuestionRepositoryInterface
{
    public function createExerciseWithQuestions(array $data);
    public function findById($id);
    public function update($id, array $data);
    public function delete($id);
}
