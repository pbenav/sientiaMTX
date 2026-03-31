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
        // Normalize priorities (critical, medium -> high, the rest -> low)
        \Illuminate\Support\Facades\DB::table('tasks')
            ->whereIn('priority', ['critical', 'medium'])
            ->update(['priority' => 'high']);
            
        \Illuminate\Support\Facades\DB::table('tasks')
            ->whereNotIn('priority', ['high', 'low'])
            ->update(['priority' => 'low']);

        // Normalize urgencies
        \Illuminate\Support\Facades\DB::table('tasks')
            ->whereIn('urgency', ['critical', 'medium'])
            ->update(['urgency' => 'high']);
            
        \Illuminate\Support\Facades\DB::table('tasks')
            ->whereNotIn('urgency', ['high', 'low'])
            ->update(['urgency' => 'low']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed for data normalization
    }
};
