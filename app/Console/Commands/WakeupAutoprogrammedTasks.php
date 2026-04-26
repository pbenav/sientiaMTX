<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use Carbon\Carbon;

class WakeupAutoprogrammedTasks extends Command
{
    protected $signature = 'app:tasks-autoprogram-wakeup {--cleanup : Realizar limpieza de ejecuciones futuras previas}';
    protected $description = 'Procesa las tareas autoprogramables para generar la siguiente ocurrencia si está dentro del preaviso definido.';

    public function handle()
    {
        if ($this->option('cleanup')) {
            $this->info('Iniciando limpieza de ocurrencias futuras redundantes...');
            $this->cleanupFutureOccurrences();
        }

        $tasks = Task::where('is_autoprogrammable', true)->get();

        foreach ($tasks as $task) {
            $settings = $task->autoprogram_settings;
            $nextAt = isset($settings['next_occurrence_at']) ? Carbon::parse($settings['next_occurrence_at']) : ($task->scheduled_date ? Carbon::parse($task->scheduled_date) : now());
            
            $leadValue = (int)($settings['lead_value'] ?? 7);
            $leadUnit = $settings['lead_unit'] ?? 'days';

            $wakeupThreshold = $nextAt->copy();
            switch ($leadUnit) {
                case 'hours': $wakeupThreshold->subHours($leadValue); break;
                case 'days': $wakeupThreshold->subDays($leadValue); break;
                case 'weeks': $wakeupThreshold->subWeeks($leadValue); break;
                case 'months': $wakeupThreshold->subMonths($leadValue); break;
                default: $wakeupThreshold->subDays($leadValue);
            }

            if (now()->greaterThanOrEqualTo($wakeupThreshold)) {
                $this->info("Despertando tarea: {$task->title} para la fecha {$nextAt->toDateString()}");
                $task->generateOccurrences();
            }
        }

        return Command::SUCCESS;
    }

    protected function cleanupFutureOccurrences()
    {
        // Buscamos tareas que tienen hijos generados pero que son autoprogramables (maestras)
        $masters = Task::where('is_autoprogrammable', true)->has('children')->get();

        foreach ($masters as $master) {
            // Eliminamos todos los hijos que estén programados a partir de mañana
            // Esto permite resetear el sistema al nuevo modelo JIT
            $deletedCount = $master->children()
                ->where('metadata->is_occurrence', true) // SOLO borramos lo que el sistema generó
                ->where('scheduled_date', '>', now()->endOfDay())
                ->where('status', 'pending')
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
