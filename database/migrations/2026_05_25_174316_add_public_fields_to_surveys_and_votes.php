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
        Schema::table('surveys', function (Blueprint $table) {
            if (!Schema::hasColumn('surveys', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_active');
            }
        });

        Schema::table('survey_votes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            if (!Schema::hasColumn('survey_votes', 'session_id')) {
                $table->string('session_id')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });

        Schema::table('survey_votes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->dropColumn('session_id');
        });
    }
};
