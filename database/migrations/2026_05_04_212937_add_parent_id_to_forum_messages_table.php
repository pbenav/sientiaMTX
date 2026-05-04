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
        if (!Schema::hasColumn('forum_messages', 'parent_id')) {
            Schema::table('forum_messages', function (Blueprint $table) {
                $table->foreignId('parent_id')->after('forum_thread_id')->nullable()->constrained('forum_messages')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('forum_messages', 'parent_id')) {
            Schema::table('forum_messages', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
