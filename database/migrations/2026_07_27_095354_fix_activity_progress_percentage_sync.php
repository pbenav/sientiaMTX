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
        // 1. Forzar progreso al 100% en actividades completadas que se hayan quedado atrás
        \App\Models\Activity::whereIn('status->value', ['completed', 'cancelled', 'done', 'approved', 'accepted', 'finished', 'triggered'])
            ->where('progress_percentage', '<', 100)
            ->update(['progress_percentage' => 100]);

        // 2. Marcar como completadas aquellas actividades que tienen progreso 100% pero no están completadas
        $activities = \App\Models\Activity::where('progress_percentage', 100)
            ->whereNotIn('status->value', ['completed', 'cancelled', 'done', 'approved', 'accepted', 'finished', 'triggered'])
            ->get();

        foreach ($activities as $activity) {
            $status = $activity->status;
            if (is_array($status)) {
                $status['value'] = 'completed';
            } else {
                $status = ['value' => 'completed'];
            }
            $activity->status = $status;
            $activity->saveQuietly();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
