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
        Schema::table('submissions', function (Blueprint $table) {
            // Drop unnecessary columns
            $columnsToDrop = [];
            if (Schema::hasColumn('submissions', 'submitted_sql')) {
                $columnsToDrop[] = 'submitted_sql';
            }
            if (Schema::hasColumn('submissions', 'chosen_answer')) {
                $columnsToDrop[] = 'chosen_answer';
            }
            if (Schema::hasColumn('submissions', 'is_correct')) {
                $columnsToDrop[] = 'is_correct';
            }
            if (Schema::hasColumn('submissions', 'error_message')) {
                $columnsToDrop[] = 'error_message';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->text('submitted_sql')->nullable();
            $table->string('chosen_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->text('error_message')->nullable();
        });
    }
};
