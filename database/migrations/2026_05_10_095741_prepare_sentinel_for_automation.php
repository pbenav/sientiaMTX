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
        // 1. Modify service_reports to support automated entries and latency metrics
        Schema::table('service_reports', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->integer('latency_ms')->nullable()->after('type')->comment('Response latency in milliseconds');
        });

        // 2. Enhance services table to support configurable monitoring parameters
        Schema::table('services', function (Blueprint $table) {
            $table->integer('check_interval_minutes')->default(15)->after('status');
            $table->timestamp('last_checked_at')->nullable()->after('status_updated_at');
            $table->string('expected_text')->nullable()->after('url')->comment('Word expected in response HTML');
            $table->integer('max_latency_ms')->default(5000)->after('check_interval_minutes')->comment('Limit for warning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['check_interval_minutes', 'last_checked_at', 'expected_text', 'max_latency_ms']);
        });

        Schema::table('service_reports', function (Blueprint $table) {
            $table->dropColumn('latency_ms');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
