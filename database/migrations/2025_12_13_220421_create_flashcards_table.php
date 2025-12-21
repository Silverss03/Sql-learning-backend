<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */    public function up(): void
    {
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained()->onDelete('set null');
            $table->string('front_content'); // Question/Term
            $table->text('back_content'); // Answer/Definition
            $table->string('tags')->nullable(); // Comma-separated tags for categorization
            $table->boolean('is_favorite')->default(false);
            
            // Leitner System fields
            $table->integer('box_number')->default(1); // 1-5 boxes in Leitner system
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->integer('correct_count')->default(0); // Total correct answers
            $table->integer('incorrect_count')->default(0); // Total incorrect answers
            
            $table->timestamps();
            
            $table->index(['student_id', 'box_number']);
            $table->index(['student_id', 'lesson_id']);
            $table->index('next_review_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};
