<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use App\Models\Task;

class FixBadAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sientia:fix-bad-assignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia asignaciones de usuarios a actividades que no pertenecen a su equipo y elimina las rémoras resultantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando asignaciones erróneas (intrusos)...');

        $activities = Activity::whereNotNull('parent_id')->with('assignedTo', 'team')->get();
        $removed = 0;
        $deleted = 0;

        $bar = $this->output->createProgressBar($activities->count());
        $bar->start();

        foreach ($activities as $activity) {
            $bar->advance();

            if (!$activity->team) continue;
            
            $teamUserIds = $activity->team->members()->pluck('users.id')->toArray();
            
            $hasDetached = false;
            foreach ($activity->assignedTo as $user) {
                if (!in_array($user->id, $teamUserIds)) {
                    $activity->assignedTo()->detach($user->id);
                    $removed++;
                    $hasDetached = true;
                }
            }
            
            if ($hasDetached && $activity->fresh()->assignedTo()->count() == 0) {
                if ($activity->progress_percentage == 0 && $activity->timeLogs()->count() == 0) {
                    $activity->forceDelete();
                    Task::where('id', $activity->id)->delete();
                    $deleted++;
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("¡Limpieza de emergencia completada!");
        $this->line("- Asignaciones intrusas eliminadas: <fg=green>{$removed}</>");
        $this->line("- Rémoras fulminadas tras quedar vacías: <fg=green>{$deleted}</>");

        return 0;
    }
}
