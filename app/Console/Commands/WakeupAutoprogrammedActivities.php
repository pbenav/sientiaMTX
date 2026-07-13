<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use Carbon\Carbon;

class WakeupAutoprogrammedActivities extends Command
{
    protected $signature = 'sientia:activities-autoprogram-wakeup {--cleanup : Realizar limpieza de ejecuciones futuras previas}';
    protected $description = 'Procesa las actividades autoprogramables para generar la siguiente ocurrencia si está dentro del preaviso definido.';

    public function handle()
    {
        if ($this->option('cleanup')) {
            $this->info('Iniciando limpieza de ocurrencias futuras redundantes...');
            $this->cleanupFutureOccurrences();
        }

        // We fetch activities where metadata->is_autoprogrammable is true.
        // We use whereJsonContains or where raw depending on the db driver. For mysql/mariadb:
        $activities = Activity::where('metadata->is_autoprogrammable', true)->get();

        foreach ($activities as $activity) {
            $settings = data_get($activity->metadata, 'autoprogram_settings', []);
            $leadValue = (int)($settings['lead_value'] ?? 7);
            $leadUnit = $settings['lead_unit'] ?? 'days';

            // Generamos todas las ocurrencias que entren en el umbral de preaviso
            // de forma iterativa en una sola ejecución. Con salvaguarda para evitar bucles infinitos.
            $safetyCounter = 0;
            while (data_get($activity->metadata, 'is_autoprogrammable', false)) {
                if ($safetyCounter >= 5) {
                    $this->warn("  [Salvaguarda] Se ha alcanzado el límite de 5 iteraciones consecutivas para '{$activity->title}' para evitar bucles infinitos de CPU.");
                    break;
                }
                $safetyCounter++;

                $settings = data_get($activity->metadata, 'autoprogram_settings', []);
                $nextAt = isset($settings['next_occurrence_at']) ? Carbon::parse($settings['next_occurrence_at']) : ($activity->scheduled_date ? Carbon::parse($activity->scheduled_date) : now());
                
                $wakeupThreshold = $nextAt->copy();
                switch ($leadUnit) {
                    case 'hours': $wakeupThreshold->subHours($leadValue); break;
                    case 'days': $wakeupThreshold->subDays($leadValue); break;
                    case 'weeks': $wakeupThreshold->subWeeks($leadValue); break;
                    case 'months': $wakeupThreshold->subMonths($leadValue); break;
                    default: $wakeupThreshold->subDays($leadValue);
                }

                if (now()->greaterThanOrEqualTo($wakeupThreshold)) {
                    $this->info("Despertando actividad: {$activity->title} para la fecha {$nextAt->toDateString()}");
                    $activity->generateOccurrences();
                    $activity->refresh(); // Refrescamos el modelo para obtener el nuevo next_occurrence_at
                } else {
                    break; // Salimos del bucle si la siguiente ya no entra en el umbral
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function cleanupFutureOccurrences()
    {
        // Buscamos actividades que tienen hijos generados pero que son autoprogramables (maestras)
        $masters = Activity::where('metadata->is_autoprogrammable', true)->has('children')->get();

        foreach ($masters as $master) {
            // Eliminamos todos los hijos que estén programados a partir de mañana
            // Esto permite resetear el sistema al nuevo modelo JIT
            $deletedCount = $master->children()
                ->where('metadata->is_occurrence', true) // SOLO borramos lo que el sistema generó
                ->where('scheduled_date', '>', now()->endOfDay())
                ->where('status->value', 'pending')
                ->get()
                ->each
                ->delete()
                ->count();
            
            if ($deletedCount > 0) {
                $this->line("  - {$master->title}: Eliminadas {$deletedCount} ocurrencias futuras.");
            }
        }
    }
}
