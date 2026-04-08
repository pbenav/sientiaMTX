<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\Team;

class CheckTasksIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check {--fix : Try to fix some issues automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the integrity of the task database (orphans, cycles, sync issues)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Iniciando 'chkdsk' de tareas...");
        $fix = $this->option('fix');

        $this->checkOrphans($fix);
        $this->checkBrokenHierarchy($fix);
        $this->checkUnassigned($fix);
        $this->checkTitleMatchHierarchy($fix);
        $this->checkCircularDependencies();
        $this->checkProgressSync($fix);

        $this->info("");
        $this->info("✅ Chequeo finalizado.");
    }

    /**
     * 1. Orphans: Tasks with team_id null or pointing to a deleted team.
     */
    protected function checkOrphans($fix)
    {
        $this->warn("1. Buscando tareas huérfanas (sin equipo válido)...");
        $orphansCount = 0;
        
        // Find tasks where team_id doesn't point to an existing team
        $orphans = Task::withTrashed()
            ->where(function($q) {
                $q->whereNull('team_id')
                  ->orWhereNotExists(function ($query) {
                      $query->select('*')
                            ->from('teams')
                            ->whereColumn('teams.id', 'tasks.team_id')
                            ->whereNull('teams.deleted_at'); // Only count if team isn't soft-deleted
                  });
            })->get();

        if ($orphans->isEmpty()) {
            $this->line("   - No hay tareas huérfanas.");
            return;
        }

        foreach ($orphans as $task) {
            $orphansCount++;
            $status = $task->trashed() ? '[TRASHED] ' : '';
            $this->error("   - {$status}Tarea #{$task->id} [{$task->title}] no tiene equipo válido (team_id: " . ($task->team_id ?? 'null') . ").");
        }
        
        $this->line("   -> Encontradas {$orphansCount} tareas huérfanas.");
        if ($fix && $orphansCount > 0) {
            if ($this->confirm("¿Seguro que quieres ELIMINAR las {$orphansCount} tareas huérfanas?", true)) {
                foreach ($orphans as $task) {
                    $task->forceDelete(); // Orphan tasks are usually garbage, force delete them
                }
                $this->info("     [FIXED] {$orphansCount} tareas huérfanas eliminadas permanentemente.");
            }
        }
    }

    /**
     * 2. Unassigned: Tasks without assigned_user_id and no pivot assignments.
     */
    protected function checkUnassigned($fix)
    {
        $this->warn("2. Buscando tareas sin asignar...");
        
        // Unassigned means: 
        // 1. attributed is NOT a template
        // 2. assigned_user_id is null
        // 3. No assignments in the task_assignments pivot table
        $unassigned = Task::where('is_template', false)
            ->whereNull('assigned_user_id')
            ->whereDoesntHave('assignments')
            ->get();

        if ($unassigned->isEmpty()) {
            $this->line("   - No hay tareas sin asignar.");
            return;
        }

        $count = $unassigned->count();
        foreach ($unassigned as $task) {
            $this->error("   - Tarea #{$task->id} [{$task->title}] no tiene ninguna asignación.");
        }

        $this->line("   -> Encontradas {$count} tareas sin asignar.");
        if ($fix && $count > 0) {
            if ($this->confirm("¿Seguro que quieres ELIMINAR las {$count} tareas sin asignar?", false)) {
                foreach ($unassigned as $task) {
                    $task->forceDelete();
                }
                $this->info("     [FIXED] {$count} tareas sin asignar eliminadas permanentemente.");
            }
        }
    }

    /**
     * 3. Broken Hierarchy: parent_id points to a non-existent task.
     */
    protected function checkBrokenHierarchy($fix)
    {
        $this->warn("3. Buscando jerarquías rotas (padre inexistente)...");
        $broken = Task::withTrashed()
            ->from('tasks as t_outer')
            ->whereNotNull('t_outer.parent_id')
            ->whereNotExists(function ($query) {
                $query->select('*')
                      ->from('tasks as t_inner')
                      ->whereColumn('t_inner.id', 't_outer.parent_id');
            })
            ->get(['t_outer.*']);

        if ($broken->isEmpty()) {
            $this->line("   - Todas las jerarquías son válidas.");
            return;
        }

        $count = $broken->count();
        foreach ($broken as $task) {
            $this->error("   - Tarea #{$task->id} [{$task->title}] tiene parent_id #{$task->parent_id} inexistente.");
            if ($fix) {
                $task->update(['parent_id' => null]);
            }
        }
        
        $this->line("   -> Encontradas {$count} jerarquías rotas.");
        if ($fix) {
            $this->info("     [FIXED] Hierarquías marcadas como null.");
        }
    }

    /**
     * 4. Title Matching Heuristic: Single tasks that match a template's title in the same team.
     */
    protected function checkTitleMatchHierarchy($fix)
    {
        $this->warn("4. Buscando re-vinculaciones por título (instancias perdidas de plantillas)...");
        
        // Find tasks that have NO parent, are NOT templates, but match the title of a Template in the SAME team
        $lostInstances = Task::whereNull('parent_id')
            ->where('is_template', false)
            ->whereExists(function($q) {
                $q->select('*')
                  ->from('tasks as templates')
                  ->whereColumn('templates.title', 'tasks.title')
                  ->whereColumn('templates.team_id', 'tasks.team_id')
                  ->where('templates.is_template', true)
                  ->whereNull('templates.deleted_at'); // Only match active templates
            })->get();

        if ($lostInstances->isEmpty()) {
            $this->line("   - No hay tareas candidatas a re-vincular por título.");
            return;
        }

        $count = $lostInstances->count();
        foreach ($lostInstances as $task) {
            $template = Task::where('is_template', true)
                ->where('team_id', $task->team_id)
                ->where('title', trim($task->title))
                ->first();

            if ($template) {
                $this->error("   - Tarea #{$task->id} [{$task->title}] debería depender de la Plantilla #{$template->id}.");
                if ($fix) {
                    if ($this->confirm("¿Quieres vincular Tarea #{$task->id} a la Plantilla #{$template->id}?", true)) {
                        $task->update(['parent_id' => $template->id]);
                        $this->info("     [FIXED] Tarea #{$task->id} vinculada.");
                    }
                }
            }
        }

        $this->line("   -> Encontradas {$count} candidatas a re-vincular.");
    }

    /**
     * 5. Circular Dependencies: Tasks that are their own ancestors.
     */
    protected function checkCircularDependencies()
    {
        $this->warn("5. Buscando dependencias circulares...");
        $tasksWithParent = Task::whereNotNull('parent_id')->get();
        $cyclesFound = 0;

        foreach ($tasksWithParent as $task) {
            $path = [$task->id];
            $current = $task;
            
            while ($current && $current->parent_id) {
                if (in_array($current->parent_id, $path)) {
                    $cyclesFound++;
                    $this->error("   - ¡CICLO detectado!: " . implode(" -> ", array_reverse($path)) . " -> " . $current->parent_id);
                    break;
                }
                
                $path[] = $current->parent_id;
                $current = Task::find($current->parent_id);
            }
        }

        if ($cyclesFound === 0) {
            $this->line("   - No se han detectado ciclos.");
        } else {
            $this->line("   -> Encontrados {$cyclesFound} ciclos.");
        }
    }

    /**
     * 6. Progress Sync: DB percentage doesn't match children average.
     */
    protected function checkProgressSync($fix)
    {
        $this->warn("6. Verificando sincronización de progreso...");
        $parents = Task::whereHas('children')->get();
        $discrepancies = 0;

        foreach ($parents as $parent) {
            $dbProgress = (int) $parent->progress_percentage;
            $calculated = (int) $parent->progress; // Utiliza el accesor de Eloquent

            if ($dbProgress !== $calculated) {
                $discrepancies++;
                $this->error("   - Tarea #{$parent->id} [{$parent->title}]: DB={$dbProgress}%, Calc={$calculated}%");
                if ($fix) {
                    $parent->update(['progress_percentage' => $calculated]);
                }
            }
        }

        if ($discrepancies === 0) {
            $this->line("   - Todo el progreso está sincronizado.");
        } else {
            $this->line("   -> Encontradas {$discrepancies} discrepancias.");
            if ($fix) {
                $this->info("     [FIXED] Porcentajes sincronizados.");
            }
        }
    }
}
