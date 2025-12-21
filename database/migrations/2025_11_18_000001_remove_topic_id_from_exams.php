<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTopicIdFromExams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exams', function (Blueprint $table) {
            // Drop the foreign key constraint first (assuming it's named 'exams_topic_id_foreign'; adjust if different)
            $table->dropForeign(['topic_id']);
            // Then drop the column
            $table->dropColumn('topic_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exams', function (Blueprint $table) {
            // Re-add the column as an unsigned big integer (nullable, assuming it was optional)
            $table->unsignedBigInteger('topic_id')->nullable()->after('id');
            // Re-add the foreign key constraint
            $table->foreign('topic_id')->references('id')->on('topics');
        });
    }
}