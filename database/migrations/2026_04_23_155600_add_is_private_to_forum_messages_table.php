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
        Schema::table('forum_messages', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('is_edited');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_messages', function (Blueprint $table) {
            $table->dropColumn('is_private');
        });
    }
};
