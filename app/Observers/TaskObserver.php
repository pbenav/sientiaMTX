<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Str;

class TaskObserver
{
    /**
     * Handle the Task "saving" event.
     */
    public function saving(Task $task): void
    {
        // Unarchive tasks that are not 100% completed
        if ($task->isDirty('progress_percentage') || $task->isDirty('status')) {
            // If status is manually set to completed/cancelled, force progress to 100
            if (in_array($task->status, ['completed', 'cancelled']) && $task->progress_percentage < 100) {
                $task->progress_percentage = 100;
            }
            
            // Inverse: If progress reaches 100, set status to completed
            if ($task->progress_percentage == 100 && !in_array($task->status, ['completed', 'cancelled'])) {
                $task->status = 'completed';
            } elseif ($task->progress_percentage > 0 && $task->progress_percentage < 100 && in_array($task->status, ['pending', 'todo'])) {
                $task->status = 'in_progress';
            } elseif ($task->progress_percentage == 0 && in_array($task->status, ['in_progress', 'completed'])) {
                $task->status = 'pending';
            }
        }

        // Cascade completion: If this task is completed, all children should be completed
        if ($task->isDirty('status') && $task->status === 'completed') {
            $task->children()->whereNotIn('status', ['completed', 'cancelled'])->each(function($child) {
                $meta = $child->metadata ?? [];
                $meta['was_incomplete_before_parent_completion'] = true;
                $meta['original_status_before_cascade'] = $child->status;
                $meta['original_progress_before_cascade'] = $child->progress_percentage;
                $child->update([
                    'status' => 'completed',
                    'progress_percentage' => 100,
                    'metadata' => $meta
                ]);
            });
        }

        // Sync archived status: If not completed, it should NOT be archived
        if ($task->status !== 'completed' && $task->is_archived) {
            $task->is_archived = false;
        }

        // Reset reminder tracking if key dates or statuses change so notifications can trigger again
        if ($task->isDirty(['due_date', 'scheduled_date', 'status', 'priority', 'urgency'])) {
            $meta = $task->metadata ?? [];
            if (isset($meta['last_reminder_sent_at'])) {
                unset($meta['last_reminder_sent_at']);
                $task->metadata = $meta;
            }
        }
    }

    /**
     * Handle the Task "saved" event.
     */
    public function saved(Task $task): void
    {
        // Sincronizar columna Kanban si el progreso o el estado cambió
        if ($task->wasChanged(['status', 'progress_percentage'])) {
            $task->syncKanbanColumn();
        }

        // Sincronizar el estado de la tarea con las citas asociadas
        if ($task->wasChanged('status')) {
            $appointments = \App\Models\Appointment::where('task_id', $task->id)->get();
            
            if ($appointments->isEmpty() && preg_match('/MTXCITA-[A-Z0-9]{8}/', $task->title, $matches)) {
                $localizador = $matches[0];
                $appointment = \App\Models\Appointment::where('localizador', $localizador)->first();
                if ($appointment) {
                    $appointment->update(['task_id' => $task->id]);
                    $appointments = collect([$appointment]);
                }
            }

            foreach ($appointments as $appointment) {
                $newStatus = null;
                if ($task->status === 'completed' && $appointment->status !== 'completed') {
                    $newStatus = 'completed';
                } elseif (in_array($task->status, ['pending', 'in_progress']) && $appointment->status === 'completed') {
                    $newStatus = 'confirmed';
                }

                if ($newStatus) {
                    $appointment->update(['status' => $newStatus]);
                }
            }
        }

        // --- Sincronización bidireccional / réplica hacia Activities ---
        $this->syncToActivity($task);
    }

    /**
     * Handle the Task "deleting" event.
     */
    public function deleting(Task $task): void
    {
        // When force deleting, we should force delete all children too
        if ($task->isForceDeleting()) {
            $task->children()->withTrashed()->each(fn (Task $child) => $child->forceDelete());
        } else {
            $task->children()->each(fn (Task $child) => $child->delete());
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        $mapping = \DB::table('activity_task_mapping')->where('task_id', $task->id)->first();
        if ($mapping) {
            $activity = \App\Models\Activity::withTrashed()->find($mapping->activity_id);
            if ($activity) {
                if ($task->isForceDeleting()) {
                    $activity->forceDelete();
                    \DB::table('activity_task_mapping')->where('task_id', $task->id)->delete();
                } else {
                    $activity->delete();
                }
            }
        }
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        $mapping = \DB::table('activity_task_mapping')->where('task_id', $task->id)->first();
        if ($mapping) {
            $activity = \App\Models\Activity::onlyTrashed()->find($mapping->activity_id);
            if ($activity) {
                $activity->restore();
            }
        }
    }

    /**
     * Helper para sincronizar Task a su Activity homóloga.
     */
    protected function syncToActivity(Task $task): void
    {
        // Evitamos recursiones
        if (static::$isSyncing) return;
        static::$isSyncing = true;

        try {
            $mapping = \DB::table('activity_task_mapping')->where('task_id', $task->id)->first();
            
            // Buscar si la tarea padre tiene mapeo para enlazar la jerarquía en la tabla de actividades
            $activityParentId = null;
            if ($task->parent_id) {
                $parentMap = \DB::table('activity_task_mapping')->where('task_id', $task->parent_id)->first();
                if ($parentMap) {
                    $activityParentId = $parentMap->activity_id;
                }
            }

            // Datos comunes a mapear
            $activityData = [
                'team_id'             => $task->team_id,
                'created_by_id'       => $task->created_by_id ?? auth()->id() ?? 1,
                'parent_id'           => $activityParentId,
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
                'auto_priority'       => $task->auto_priority ?? false,
                'progress_percentage' => $task->progress_percentage ?? 0,
                'kanban_column_id'    => $task->kanban_column_id,
                'kanban_order'        => $task->kanban_order,
                'matrix_order'        => $task->matrix_order,
                'is_archived'          => $task->is_archived ?? false,
                'is_template'         => $task->is_template ?? false,
                'google_task_id'      => $task->google_task_id,
                'google_task_list_id' => $task->google_task_list_id,
                'google_calendar_event_id' => $task->google_calendar_event_id,
                'google_calendar_id'  => $task->google_calendar_id,
                'google_synced_at'    => $task->google_synced_at,
                // Metadatos específicos de la tarea
                'metadata' => [
                    'urgency'              => $task->urgency ?? 'medium',
                    'cognitive_load'       => $task->cognitive_load ?? 1,
                    'is_out_of_skill_tree' => $task->is_out_of_skill_tree ?? false,
                    'autoprogram_settings' => $task->autoprogram_settings,
                    'service_id'           => $task->service_id,
                    'skill_id'             => $task->skill_id,
                    'impact_human_metric'  => $task->impact_human_metric ?? 0,
                ]
            ];

            if ($mapping) {
                $activity = \App\Models\Activity::withTrashed()->find($mapping->activity_id);
                if ($activity) {
                    $activity->update($activityData);
                }
            } else {
                $activity = \App\Models\Activity::create($activityData);
                
                \DB::table('activity_task_mapping')->insert([
                    'task_id'     => $task->id,
                    'activity_id' => $activity->id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Sincronizar asignaciones
            $this->syncAssignments($task, $activity);

            // Sincronizar etiquetas
            $this->syncTags($task, $activity);

        } finally {
            static::$isSyncing = false;
        }
    }

    public static bool $isSyncing = false;

    /**
     * Sincroniza asignaciones de Task a Activity
     */
    protected function syncAssignments(Task $task, \App\Models\Activity $activity): void
    {
        $activity->assignments()->delete();

        $assignments = $task->assignments()->get();
        $assignedUserIds = [];

        foreach ($assignments as $a) {
            if ($a->user_id) {
                $assignedUserIds[] = (int) $a->user_id;
            }
            \App\Models\ActivityAssignment::create([
                'activity_id'    => $activity->id,
                'user_id'        => $a->user_id,
                'group_id'       => $a->group_id,
                'assigned_by_id' => $a->assigned_by_id ?? 1,
                'assigned_at'    => $a->assigned_at ?? now(),
            ]);
        }

        if ($task->assigned_user_id && !in_array((int) $task->assigned_user_id, $assignedUserIds)) {
            \App\Models\ActivityAssignment::create([
                'activity_id'    => $activity->id,
                'user_id'        => $task->assigned_user_id,
                'group_id'       => null,
                'assigned_by_id' => $task->created_by_id ?? 1,
                'assigned_at'    => $task->created_at ?? now(),
            ]);
        }
    }

    /**
     * Sincroniza etiquetas de Task a Activity
     */
    protected function syncTags(Task $task, \App\Models\Activity $activity): void
    {
        $activity->tags()->delete();

        $tags = $task->tags()->get();
        foreach ($tags as $t) {
            $activity->tags()->create([
                'tag'       => $t->tag,
                'color_hex' => $t->color_hex ?? '#6b7280',
            ]);
        }
    }
}
