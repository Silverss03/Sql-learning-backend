<?php

namespace App\Repositories\Interfaces;

interface TopicRepositoryInterface
{
    public function getAllActive();
    public function findActiveById($id);
    public function getLessonsByTopic($topicId);
    public function getChapterExercisesByTopic($topicId, $studentId);
    public function getTopicProgress($topicId, $studentId);
}
