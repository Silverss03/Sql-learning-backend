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
        // Add `score` to submissions table
        Schema::table('submissions', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('is_correct'); // Score out of 10
        });

        // Add `avg_score` to students table
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('avg_score', 5, 2)->default(0)->after('student_code'); // Average score
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('avg_score');
        });
    }
};
