<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            \App\Repositories\Interfaces\TopicRepositoryInterface::class,
            \App\Repositories\Implementations\TopicRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\LessonRepositoryInterface::class,
            \App\Repositories\Implementations\LessonRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\StudentRepositoryInterface::class,
            \App\Repositories\Implementations\StudentRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ExamRepositoryInterface::class,
            \App\Repositories\Implementations\ExamRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ChapterExerciseRepositoryInterface::class,
            \App\Repositories\Implementations\ChapterExerciseRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\QuestionRepositoryInterface::class,
            \App\Repositories\Implementations\QuestionRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Interfaces\TeacherRepositoryInterface::class,
            \App\Repositories\Implementations\TeacherRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\LessonExerciseRepositoryInterface::class,
            \App\Repositories\Implementations\LessonExerciseRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ExamAuditLogRepositoryInterface::class,
            \App\Repositories\Implementations\ExamAuditLogRepository::class
        );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event listeners are auto-discovered by Laravel
        // No manual registration needed for listeners in app/Listeners folder
        
        try {
            Storage::extend('google', function($app, $config) {
                $options = [];

                if (!empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                if (!empty($config['sharedFolderId'] ?? null)) {
                    $options['sharedFolderId'] = $config['sharedFolderId'];
                }

                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);
                
                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch(\Exception $e) {
            // your exception handling logic
        }
    }
}
