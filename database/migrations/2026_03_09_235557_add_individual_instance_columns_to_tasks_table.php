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
            $table->boolean('is_template')->default(false)->after('team_id');
            $table->foreignId('assigned_user_id')->nullable()->after('parent_id')->constrained('users')->onDelete('cascade');
            // 'blocked' status is handled via the string status field if it's already a string, 
            // otherwise we'd need to change the column type. Let's assume it's a string based on TaskController.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn(['is_template', 'assigned_user_id']);
        });
    }
};
