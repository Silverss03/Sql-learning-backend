<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\TopicRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Repositories\Interfaces\LessonRepositoryInterface;
use App\Repositories\Interfaces\LessonExerciseRepositoryInterface;
use App\Repositories\Interfaces\ExamRepositoryInterface;
use App\Repositories\Interfaces\ChapterExerciseRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Implementations\TopicRepository;
use App\Repositories\Implementations\ExamRepository;
use App\Repositories\Implementations\ChapterExerciseRepository;
use App\Repositories\Implementations\QuestionRepository;
use App\Repositories\Implementations\StudentRepository;
use App\Repositories\Implementations\LessonRepository;
use App\Repositories\Implementations\LessonExerciseRepository;

class RepositoryServiceProvider extends ServiceProvider
{    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            TopicRepositoryInterface::class,
            TopicRepository::class
        );

        $this->app->bind(
            StudentRepositoryInterface::class,
            StudentRepository::class
        );

        $this->app->bind(
            LessonRepositoryInterface::class,
            LessonRepository::class
        );

        $this->app->bind(
            ExamRepositoryInterface::class,
            ExamRepository::class
        );        $this->app->bind(
            ChapterExerciseRepositoryInterface::class,
            ChapterExerciseRepository::class
        );

        $this->app->bind(
            LessonExerciseRepositoryInterface::class,
            LessonExerciseRepository::class
        );

        $this->app->bind(
            QuestionRepositoryInterface::class,
            QuestionRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
