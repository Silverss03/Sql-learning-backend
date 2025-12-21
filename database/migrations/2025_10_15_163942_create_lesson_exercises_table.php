<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only create lesson_exercises table if it doesn't exist
        if (!Schema::hasTable('lesson_exercises')) {
            Schema::create('lesson_exercises', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lesson_id');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Clear questions table to avoid foreign key constraint issues
        DB::table('questions')->delete();

        // Update the questions table
        Schema::table('questions', function (Blueprint $table) {
            // Only add lesson_exercise_id column if it doesn't exist
            if (!Schema::hasColumn('questions', 'lesson_exercise_id')) {
                $table->unsignedBigInteger('lesson_exercise_id')->after('id');
                $table->foreign('lesson_exercise_id')->references('id')->on('lesson_exercises')->onDelete('cascade');
            }
        });

        // Update the submissions table
        Schema::table('submissions', function (Blueprint $table) {
            // Drop foreign key constraints first
            try {
                $table->dropForeign(['question_id']);
            } catch (Exception $e) {
                // Foreign key doesn't exist, continue
            }
            
            // Only drop columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('submissions', 'question_id')) {
                $columnsToDrop[] = 'question_id';
            }
            if (Schema::hasColumn('submissions', 'question_type')) {
                $columnsToDrop[] = 'question_type';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Only add lesson_exercise_id if it doesn't exist
            if (!Schema::hasColumn('submissions', 'lesson_exercise_id')) {
                $table->unsignedBigInteger('lesson_exercise_id')->after('student_id');
                $table->foreign('lesson_exercise_id')->references('id')->on('lesson_exercises')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
        public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'lesson_id')) {
                $table->unsignedBigInteger('lesson_id')->after('id');
                $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
            }
            
            if (Schema::hasColumn('questions', 'lesson_exercise_id')) {
                $table->dropForeign(['lesson_exercise_id']);
                $table->dropColumn('lesson_exercise_id');
            }
        });

        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'question_id')) {
                $table->unsignedBigInteger('question_id')->after('student_id');
            }
            if (!Schema::hasColumn('submissions', 'question_type')) {
                $table->string('question_type')->after('question_id');
            }
            
            if (Schema::hasColumn('submissions', 'lesson_exercise_id')) {
                $table->dropForeign(['lesson_exercise_id']);
                $table->dropColumn('lesson_exercise_id');
            }
        });

        Schema::dropIfExists('lesson_exercises');
    }
};
