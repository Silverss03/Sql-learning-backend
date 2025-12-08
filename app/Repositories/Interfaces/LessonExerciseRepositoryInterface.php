<?php

namespace App\Repositories\Interfaces;

interface LessonExerciseRepositoryInterface
{
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findById($id);
}
