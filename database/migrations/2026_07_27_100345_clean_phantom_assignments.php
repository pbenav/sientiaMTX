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
        // Limpiar asignaciones "fantasma" donde el usuario o grupo ha sido eliminado (ON DELETE SET NULL)
        \Illuminate\Support\Facades\DB::table('activity_assignments')
            ->whereNull('user_id')
            ->whereNull('group_id')
            ->delete();

        \Illuminate\Support\Facades\DB::table('task_assignments')
            ->whereNull('user_id')
            ->whereNull('group_id')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
