<?php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RestructureProgressTablesAndDropSubmissions extends Migration
{
    public function up()
    {
        // Add 'score' and 'submitted_at' to student_lesson_progress
        Schema::table('student_lesson_progress', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('finished_at');
            $table->timestamp('submitted_at')->nullable()->after('score');
            
            // Drop unique constraint to allow multiple submissions per lesson
            // Adjust the index name if it differs in your database
            $table->dropUnique('student_lesson_progress_student_id_lesson_id_unique'); 
        });

        // Modify student_chapter_exercise_progress to allow multiple submissions
        Schema::table('student_chapter_exercise_progress', function (Blueprint $table) {
            // Rename 'completed_at' to 'submitted_at' for consistency
            $table->renameColumn('completed_at', 'submitted_at');
            
            // Drop unique constraint to allow multiple submissions per chapter exercise
            $table->dropUnique('student_chapter_exercise_unique');
        });

        // Drop the submissions table
        Schema::dropIfExists('submissions');
    }

    public function down()
    {
        // Recreate submissions table (for rollback)
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('lesson_exercise_id')->nullable();
            $table->unsignedBigInteger('chapter_exercise_id')->nullable();
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->unsignedBigInteger('question_id')->nullable();
            $table->decimal('score', 5, 2);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('lesson_exercise_id')->references('id')->on('lesson_exercises')->onDelete('cascade');
            $table->foreign('chapter_exercise_id')->references('id')->on('chapter_exercises')->onDelete('cascade');
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
        });

        // Revert student_chapter_exercise_progress
        Schema::table('student_chapter_exercise_progress', function (Blueprint $table) {
            $table->renameColumn('submitted_at', 'completed_at');
            $table->unique(['student_id', 'chapter_exercise_id']);
        });

        // Revert student_lesson_progress
        Schema::table('student_lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['score', 'submitted_at']);
            $table->unique(['student_id', 'lesson_id']);
        });
    }
}