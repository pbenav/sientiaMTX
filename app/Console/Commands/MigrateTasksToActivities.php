<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Activity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTasksToActivities extends Command
{
    protected $signature = 'mtx:migrate-tasks-to-activities {--force : Sobrescribir mapeos existentes}';
    protected $description = 'Migra todas las tareas (tasks) existentes a la nueva tabla unificada de actividades (activities)';

    public function handle(): int
    {
        $this->info('Iniciando migración de Tasks a Activities...');

        $force = $this->option('force');
        if ($force) {
            $this->warn('Opción --force activa. Se limpiarán actividades de tipo task y la tabla de mapeo.');
            if ($this->confirm('¿Estás seguro de que quieres continuar? Esto borrará el mapeo actual.')) {
                DB::table('activity_task_mapping')->truncate();
                Activity::where('type', 'task')->delete();
            } else {
                $this->info('Operación cancelada.');
                return Command::SUCCESS;
            }
        }

        // Obtener tareas no migradas aún
        $query = Task::withTrashed();
        if (!$force) {
            $migratedIds = DB::table('activity_task_mapping')->pluck('task_id');
            $query->whereNotIn('id', $migratedIds);
        }

        $tasksCount = $query->count();
        if ($tasksCount === 0) {
            $this->info('No hay tareas pendientes de migrar.');
            return Command::SUCCESS;
        }

        $this->info("Se migrarán {$tasksCount} tareas...");
        $bar = $this->output->createProgressBar($tasksCount);
        $bar->start();

        // Primera pasada: crear actividades y mapear
        $query->chunk(100, function ($tasks) use ($bar) {
            foreach ($tasks as $task) {
                DB::transaction(function () use ($task) {
                    // Mapear datos a Actividad
                    $activity = Activity::create([
                        'team_id'             => $task->team_id,
                        'created_by_id'       => $task->created_by_id ?? 1,
                        'parent_id'           => null, // Se rellenará en la segunda pasada para asegurar existencia de IDs mapeados
                        'expediente_id'       => $task->expediente_id,
                        'type'                => 'task',
                        'title'               => $task->title,
                        'description'         => $task->description,
                        'status'              => ['value' => $task->status],
                        'visibility'          => $task->visibility === 'private' ? 'private' : 'public',
                        'due_date'            => $task->due_date,
                        'scheduled_date'      => $task->scheduled_date,
                        'original_due_date'   => $task->original_due_date,
                        'priority'            => $task->priority,
                        'auto_priority'       => $task->auto_priority,
                        'progress_percentage' => $task->progress_percentage,
                        'kanban_column_id'    => $task->kanban_column_id,
                        'kanban_order'        => $task->kanban_order,
                        'matrix_order'        => $task->matrix_order,
                        'is_archived'          => $task->is_archived,
                        'is_template'         => $task->is_template,
                        'google_task_id'      => $task->google_task_id,
                        'google_task_list_id' => $task->google_task_list_id,
                        'google_calendar_event_id' => $task->google_calendar_event_id,
                        'google_calendar_id'  => $task->google_calendar_id,
                        'google_synced_at'    => $task->google_synced_at,
                        'deleted_at'          => $task->deleted_at,
                        'metadata' => [
                            'urgency'              => $task->urgency ?? 'medium',
                            'cognitive_load'       => $task->cognitive_load ?? 1,
                            'is_out_of_skill_tree' => $task->is_out_of_skill_tree ?? false,
                            'autoprogram_settings' => $task->autoprogram_settings,
                            'service_id'           => $task->service_id,
                            'skill_id'             => $task->skill_id,
                            'impact_human_metric'  => $task->impact_human_metric ?? 0,
                        ]
                    ]);

                    // Registrar mapeo
                    DB::table('activity_task_mapping')->insert([
                        'task_id'     => $task->id,
                        'activity_id' => $activity->id,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    // Copiar asignaciones
                    foreach ($task->assignments as $a) {
                        DB::table('activity_assignments')->insert([
                            'activity_id'    => $activity->id,
                            'user_id'        => $a->user_id,
                            'group_id'       => $a->group_id,
                            'assigned_by_id' => $a->assigned_by_id ?? 1,
                            'assigned_at'    => $a->assigned_at ?? now(),
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }

                    // Copiar etiquetas
                    foreach ($task->tags as $t) {
                        DB::table('activity_tags')->insert([
                            'activity_id' => $activity->id,
                            'tag'         => $t->tag,
                            'color_hex'   => $t->color_hex ?? '#6b7280',
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                });

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Actividades creadas y mapeadas. Rellenando relaciones jerárquicas (parent_id)...');

        // Segunda pasada: rellenar parent_id traduciendo de Task a Activity
        $tasksWithParents = Task::withTrashed()->whereNotNull('parent_id')->get();
        $parentCount = 0;
        
        foreach ($tasksWithParents as $task) {
            $mapping = DB::table('activity_task_mapping')->where('task_id', $task->id)->first();
            $parentMapping = DB::table('activity_task_mapping')->where('task_id', $task->parent_id)->first();

            if ($mapping && $parentMapping) {
                Activity::withTrashed()
                    ->where('id', $mapping->activity_id)
                    ->update(['parent_id' => $parentMapping->activity_id]);
                $parentCount++;
            }
        }

        $this->info("¡Completado con éxito! Se han jerarquizado {$parentCount} actividades.");
        return Command::SUCCESS;
    }
}
