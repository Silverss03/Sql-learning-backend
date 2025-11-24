<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('submissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Recreate the table if rollback is needed (adjust columns as per your original schema)
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('lesson_exercise_id')->nullable();
            $table->unsignedBigInteger('chapter_exercise_id')->nullable();
            $table->json('answers')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('lesson_exercise_id')->references('id')->on('lesson_exercises');
            $table->foreign('chapter_exercise_id')->references('id')->on('chapter_exercises');
        });
    }
};
