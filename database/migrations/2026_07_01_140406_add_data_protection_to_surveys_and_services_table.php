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
            $table->json('data_protection')->nullable();
        });

        Schema::table('appointment_services', function (Blueprint $table) {
            $table->json('data_protection')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('data_protection');
        });

        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropColumn('data_protection');
        });
    }
};
