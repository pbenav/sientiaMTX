<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use App\Services\ActivityService;

class AutocompleteMeetings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:autocomplete-meetings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca como completadas automáticamente las reuniones que ya han superado su fecha de finalización.';

    /**
     * Execute the console command.
     */
    public function handle(ActivityService $activityService)
    {
        // Buscar todas las reuniones ('meeting') que estén pendientes o en progreso (no completadas/canceladas)
        // y cuya due_date ya haya pasado, O cuya scheduled_date + duration ya haya pasado (con un margen de seguridad de 2h).
        
        $activities = Activity::where('type', 'meeting')
            ->whereJsonDoesntContain('status->value', 'completed')
            ->whereJsonDoesntContain('status->value', 'cancelled')
            ->whereJsonDoesntContain('status->value', 'blocked')
            ->get();

        $completedCount = 0;

        foreach ($activities as $meeting) {
            $shouldComplete = false;

            // 1. Si tiene due_date explícita y ha pasado
            if ($meeting->due_date && $meeting->due_date->isPast()) {
                $shouldComplete = true;
            }
            // 2. Si no tiene due_date, usamos scheduled_date + duration (o 2h por defecto)
            elseif ($meeting->scheduled_date) {
                $duration = $meeting->metadata['duration_minutes'] ?? 60;
                // Le damos un margen de gracia de 2 horas adicionales por si la reunión se alarga
                $estimatedEnd = $meeting->scheduled_date->copy()->addMinutes($duration)->addHours(2);
                
                if ($estimatedEnd->isPast()) {
                    $shouldComplete = true;
                }
            }

            if ($shouldComplete) {
                $activityService->changeStatus($meeting, 'completed');
                $meeting->update(['progress_percentage' => 100]);
                $this->info("Reunión auto-completada: {$meeting->title} (ID: {$meeting->id})");
                $completedCount++;
            }
        }

        $this->info("Proceso terminado. {$completedCount} reuniones completadas automáticamente.");
    }
}
