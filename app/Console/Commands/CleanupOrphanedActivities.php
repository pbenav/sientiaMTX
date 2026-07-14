<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use App\Models\Task;

class CleanupOrphanedActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sientia:cleanup-orphaned-activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia y reasigna instancias de actividades huérfanas provenientes de migraciones antiguas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando análisis de instancias huérfanas...');

        $orphans = Activity::whereNotNull('parent_id')->doesntHave('assignedTo')->get();
        $totalOrphans = $orphans->count();

        if ($totalOrphans === 0) {
            $this->info('No se encontraron actividades huérfanas. ¡Todo está en orden!');
            return 0;
        }

        $this->warn("Se encontraron {$totalOrphans} instancias sin asignar.");

        $deleted = 0;
        $recovered = 0;

        $bar = $this->output->createProgressBar($totalOrphans);
        $bar->start();

        foreach ($orphans as $activity) {
            $parent = $activity->parent;
            $assignedUserId = null;

            // 1. Si no tiene padre, es una instancia totalmente rota (dead orphan)
            if (!$parent) {
                $activity->forceDelete();
                Task::where('id', $activity->id)->delete();
                $deleted++;
                $bar->advance();
                continue;
            }

            // 2. Intentar recuperar desde el modelo legacy Task
            $task = Task::find($activity->id);
            if ($task) {
                if ($task->assigned_user_id) {
                    $assignedUserId = $task->assigned_user_id;
                } elseif ($task->assignments()->count() > 0) {
                    $assignedUserId = $task->assignments()->first()->user_id;
                }
            }

            // 3. Intentar recuperar desde los registros de tiempo (TimeLogs)
            if (!$assignedUserId && $activity->timeLogs()->count() > 0) {
                $assignedUserId = $activity->timeLogs()->first()->user_id;
            }

            // 4. Intentar recuperar desde el creador de la instancia (si es distinto al creador del plan maestro)
            if (!$assignedUserId && $activity->created_by_id && $activity->created_by_id !== $parent->created_by_id) {
                $assignedUserId = $activity->created_by_id;
            }

            // Si hemos encontrado a quién asignarlo...
            if ($assignedUserId) {
                $activity->assignedTo()->syncWithoutDetaching([
                    $assignedUserId => [
                        'assigned_by_id' => $activity->created_by_id ?? 1,
                        'assigned_at' => now()
                    ]
                ]);
                $recovered++;
            } else {
                // No se pudo recuperar de ninguna manera. Es una rémora. Se elimina de forma segura.
                $activity->forceDelete();
                if ($task) {
                    $task->delete();
                }
                $deleted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('¡Limpieza completada con éxito!');
        $this->line("<fg=green>Recuperadas y reasignadas:</> {$recovered}");
        $this->line("<fg=red>Eliminadas de forma segura (rémoras):</> {$deleted}");

        return 0;
    }
}
