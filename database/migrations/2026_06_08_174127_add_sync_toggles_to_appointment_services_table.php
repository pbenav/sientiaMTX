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
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->boolean('sync_to_google_calendar')->default(false)->after('price_visible');
            $table->boolean('sync_to_google_tasks')->default(false)->after('sync_to_google_calendar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropColumn(['sync_to_google_calendar', 'sync_to_google_tasks']);
        });
    }
};
