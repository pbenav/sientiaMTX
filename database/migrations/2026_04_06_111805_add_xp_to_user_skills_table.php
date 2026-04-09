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
        Schema::table('user_skills', function (Blueprint $table) {
            if (!Schema::hasColumn('user_skills', 'total_xp')) {
                $table->unsignedBigInteger('total_xp')->default(0)->after('level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_skills', function (Blueprint $table) {
            $table->dropColumn('total_xp');
        });
    }
};
