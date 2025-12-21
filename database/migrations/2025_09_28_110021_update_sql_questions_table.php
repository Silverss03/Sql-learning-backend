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
        Schema::table('sql_questions', function (Blueprint $table) {
            $table->dropColumn(['setup_sql', 'teardown_sql']);

            // Add new ones
            $table->foreignId('question_schema_id')
                ->nullable()
                ->constrained('question_schemas')
                ->onDelete('restrict');

            $table->text('expected_result_query')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sql_questions', function (Blueprint $table) {
            $table->string('setup_sql')->nullable();
            $table->string('teardown_sql')->nullable();

            // Remove added columns
            $table->dropForeign(['question_schema_id']);
            $table->dropColumn(['question_schema_id', 'expected_result_query']);
        });
    }
};
