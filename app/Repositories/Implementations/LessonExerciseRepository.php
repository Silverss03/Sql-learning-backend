<?php

namespace App\Repositories\Implementations;

use App\Models\LessonExercise;
use App\Repositories\Interfaces\LessonExerciseRepositoryInterface;

class LessonExerciseRepository implements LessonExerciseRepositoryInterface
{
    public function create(array $data)
    {
        return LessonExercise::create($data);
    }

    public function update($id, array $data)
    {
        $lessonExercise = LessonExercise::find($id);
        
        if (!$lessonExercise) {
            return null;
        }

        $lessonExercise->update($data);
        return $lessonExercise->fresh();
    }

    public function delete($id)
    {
        $lessonExercise = LessonExercise::find($id);
        
        if (!$lessonExercise) {
            return false;
        }

        return $lessonExercise->delete();
    }

    public function findById($id)
    {
        return LessonExercise::find($id);
    }
}
