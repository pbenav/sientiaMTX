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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('google_task_id')->nullable()->after('visibility');
            $table->string('google_task_list_id')->nullable()->after('google_task_id');
            $table->timestamp('google_synced_at')->nullable()->after('google_task_list_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['google_task_id', 'google_task_list_id', 'google_synced_at']);
        });
    }
};
