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
        Schema::create('multiple_choices_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->references('id')->on('questions')->onDelete('cascade');
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multiple_choices_questions');
    }
};
