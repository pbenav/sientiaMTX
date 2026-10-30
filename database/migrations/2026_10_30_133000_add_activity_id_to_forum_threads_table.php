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
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->foreignId('activity_id')->after('task_id')->nullable()->constrained('activities')->nullOnDelete();
        });

        // Backfill activity_id for existing threads mapped through activity_task_mapping
        if (Schema::hasTable('activity_task_mapping')) {
            \DB::statement("
                UPDATE forum_threads ft
                INNER JOIN activity_task_mapping atm ON ft.task_id = atm.task_id
                SET ft.activity_id = atm.activity_id
                WHERE ft.activity_id IS NULL AND ft.task_id IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropColumn('activity_id');
        });
    }
};

