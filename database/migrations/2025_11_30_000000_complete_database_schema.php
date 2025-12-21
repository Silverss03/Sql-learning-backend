<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates all tables matching the sql_learning_app database schema.
     * Generated from: sql_learning_app (1).sql
     * Date: November 30, 2025
     */
    public function up(): void
    {
        // 1. Users table (base table for authentication)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('name');
            $table->enum('role', ['admin', 'student', 'teacher'])->default('student');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        // 2. Password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // 4. Personal access tokens (Laravel Sanctum)
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // 5. Admins table
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 6. Teachers table
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 7. Classes table
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('class_code')->unique();
            $table->string('class_name')->nullable();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->string('semester')->nullable();
            $table->string('academic_year')->nullable();
            $table->integer('max_students')->default(35);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 8. Students table
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->unique();
            $table->decimal('avg_score', 5, 2)->default(0.00);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        // 9. Topics table
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('topic_name')->unique();
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamps();
        });

        // 10. Lessons table
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('lesson_title')->nullable();
            $table->longText('lesson_content');
            $table->string('slug')->nullable();
            $table->integer('estimated_time')->default(5);
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 11. Lesson exercises table
        Schema::create('lesson_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        // 12. Chapter exercises table
        Schema::create('chapter_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->text('description')->nullable();
        });

        // 13. Exams table
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });        // 14. Questions table
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_exercise_id')->nullable()
                ->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->enum('question_type', ['multiple_choice', 'sql'])->default('multiple_choice');
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('question_title')->nullable();
            $table->timestamps();
            $table->foreignId('chapter_exercise_id')->nullable()
                ->constrained('chapter_exercises')->onDelete('cascade');
            $table->foreignId('exam_id')->nullable()->constrained()->onDelete('cascade');
        });        // 15. Multiple choice questions table
        Schema::create('multiple_choices_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('description')->nullable();
            $table->string('answer_A')->nullable();
            $table->string('answer_B')->nullable();
            $table->string('answer_C')->nullable();
            $table->string('answer_D')->nullable();
            $table->enum('correct_answer', ['A', 'B', 'C', 'D'])->default('A');
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 16. Interactive SQL questions table
        Schema::create('interactive_sql_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('description')->nullable();
            $table->enum('interaction_type', ['drag_drop', 'fill_blanks']);
            $table->json('question_data');
            $table->json('solution_data');
            $table->timestamps();
        });

        // 17. Student lesson progress table
        Schema::create('student_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->timestamp('finished_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            
            $table->index('student_id');
            $table->index('lesson_id');
        });

        // 18. Student chapter exercise progress table
        Schema::create('student_chapter_exercise_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('chapter_exercise_id')->constrained('chapter_exercises')->onDelete('cascade');
            $table->integer('score')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            
            $table->index(['student_id', 'chapter_exercise_id'], 'student_chapter_exercise_index');
        });

        // 19. Student exam progress table
        Schema::create('student_exam_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->decimal('score', 5, 2)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('session_token')->unique();
            $table->string('device_fingerprint')->nullable();
            $table->timestamps();
        });

        // 20. Exam audit logs table
        Schema::create('exam_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_token');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', [
                'app_minimized',
                'app_resumed',
                'screen_capture_detected',
                'tab_switch'
            ]);
            $table->timestamps();
        });

        // 21. Cache table
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        // 22. Cache locks table
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // 23. Failed jobs table
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // 24. Jobs table
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // 25. Job batches table
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to respect foreign key constraints
        Schema::dropIfExists('exam_audit_logs');
        Schema::dropIfExists('student_exam_progress');
        Schema::dropIfExists('student_chapter_exercise_progress');
        Schema::dropIfExists('student_lesson_progress');
        Schema::dropIfExists('interactive_sql_questions');
        Schema::dropIfExists('multiple_choices_questions');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('chapter_exercises');
        Schema::dropIfExists('lesson_exercises');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
