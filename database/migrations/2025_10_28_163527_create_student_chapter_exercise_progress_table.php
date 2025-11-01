<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_chapter_exercise_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('chapter_exercise_id')->constrained('chapter_exercises')->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->integer('score')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'chapter_exercise_id'], 'student_chapter_exercise_unique');
            $table->index(['student_id', 'chapter_exercise_id'], 'student_chapter_exercise_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_chapter_exercise_progress');
    }
};
