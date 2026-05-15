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
        Schema::table('users', function (Blueprint $table) {
            $table->json('work_days_1')->nullable()->after('work_end_time_1');
            $table->json('work_days_2')->nullable()->after('work_end_time_2');
        });

        // Initialize existing users with Mon-Fri defaults
        $defaultDays = json_encode(['mon', 'tue', 'wed', 'thu', 'fri']);
        \Illuminate\Support\Facades\DB::table('users')->update([
            'work_days_1' => $defaultDays,
            'work_days_2' => $defaultDays,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['work_days_1', 'work_days_2']);
        });
    }
};
