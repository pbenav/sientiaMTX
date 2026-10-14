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
        Schema::table('team_folders', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('description');
            $table->index(['team_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_folders', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'visibility']);
            $table->dropColumn('visibility');
        });
    }
};

