<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove user_id from device_tokens table as it creates redundancy.
     * Device tokens belong to students directly (only students receive notifications).
     * If we need to get the user, we can access it via student->user relationship.
     */    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropColumn('user_id');
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Re-add user_id column
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            
            // Re-add the index
            $table->index(['user_id', 'is_active']);
            
            // Make student_id nullable again
            $table->dropForeign(['student_id']);
            $table->foreignId('student_id')->nullable()->change();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }
};
