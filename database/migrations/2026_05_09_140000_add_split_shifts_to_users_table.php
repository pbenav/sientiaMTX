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
            $table->string('work_start_time_1')->nullable()->default('08:00');
            $table->string('work_end_time_1')->nullable()->default('14:00');
            $table->string('work_start_time_2')->nullable()->default('15:00');
            $table->string('work_end_time_2')->nullable()->default('18:00');
        });

        // Migrate existing values if any
        try {
            \Illuminate\Support\Facades\DB::table('users')->update([
                'work_start_time_1' => \Illuminate\Support\Facades\DB::raw('COALESCE(work_start_time, \'08:00\')'),
                'work_end_time_1' => \Illuminate\Support\Facades\DB::raw('COALESCE(work_end_time, \'14:00\')'),
            ]);
        } catch (\Exception $e) {
            // Ignorar si hay algún problema con la base de datos
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'work_start_time_1',
                'work_end_time_1',
                'work_start_time_2',
                'work_end_time_2',
            ]);
        });
    }
};
