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
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });

        Schema::table('kudos', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });

        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::table('kudos', function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
        });

        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
        });
    }
};
