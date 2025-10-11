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
        // Drop the sql_questions table
        Schema::dropIfExists('sql_questions');

        // Drop the question_schemas table
        Schema::dropIfExists('question_schemas');

        // Create the interactive_sql_questions table
        Schema::create('interactive_sql_questions', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('question_id'); // Foreign key to questions table
            $table->enum('interaction_type', ['drag_drop', 'fill_blanks']);
            $table->json('question_data'); // JSON data for the question
            $table->json('solution_data'); // JSON data for the solution
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the sql_questions table
        Schema::create('sql_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->timestamps();
        });

        // Restore the question_schemas table
        Schema::create('question_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('schema_name');
            $table->timestamps();
        });

        // Drop the interactive_sql_questions table
        Schema::dropIfExists('interactive_sql_questions');

        // Revert changes to the submissions table
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('error_message')->nullable();
            $table->dropColumn('question_type');
        });
    }
};
