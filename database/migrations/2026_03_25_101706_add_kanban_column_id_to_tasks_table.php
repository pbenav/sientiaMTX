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
            if (!Schema::hasColumn('tasks', 'kanban_column_id')) {
                $table->foreignId('kanban_column_id')->nullable()->constrained('kanban_columns')->onDelete('set null');
            }
            if (!Schema::hasColumn('tasks', 'kanban_order')) {
                $table->integer('kanban_order')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'kanban_column_id')) {
                $table->dropForeign(['kanban_column_id']);
                $table->dropColumn(['kanban_column_id']);
            }
            if (Schema::hasColumn('tasks', 'kanban_order')) {
                $table->dropColumn(['kanban_order']);
            }
        });
    }
};
