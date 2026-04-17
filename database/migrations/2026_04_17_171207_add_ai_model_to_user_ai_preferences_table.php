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
        Schema::table('user_ai_preferences', function (Blueprint $table) {
            $table->string('ai_model')->default('gemini-1.5-flash-latest')->after('default_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_ai_preferences', function (Blueprint $table) {
            $table->dropColumn('ai_model');
        });
    }
};
