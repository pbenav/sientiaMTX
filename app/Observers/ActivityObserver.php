<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Task;

class ActivityObserver
{
    public static bool $isSyncing = false;

    public function saved(Activity $activity): void
    {
        if (static::$isSyncing || TaskObserver::$isSyncing) {
            return;
        }

        static::$isSyncing = true;

        try {
            if ($activity->type === 'task') {
                $mapping = \DB::table('activity_task_mapping')
                    ->where('activity_id', $activity->id)
                    ->first();

                if ($mapping) {
                    $task = Task::withTrashed()->find($mapping->task_id);
                    if ($task) {
                        $taskParentId = null;
                        if ($activity->parent_id) {
                            $parentMap = \DB::table('activity_task_mapping')
                                ->where('activity_id', $activity->parent_id)
                                ->first();
                            if ($parentMap) {
                                $taskParentId = $parentMap->task_id;
                            }
                        }

                        $taskData = [
                            'team_id'             => $activity->team_id,
                            'created_by_id'       => $activity->created_by_id,
                            'parent_id'           => $taskParentId,
                            'expediente_id'       => $activity->expediente_id,
                            'title'               => $activity->title,
                            'description'         => $activity->description,
                            'status'              => $activity->status_value,
                            'visibility'          => $activity->visibility === 'private' ? 'private' : 'public',
                            'due_date'            => $activity->due_date,
                            'scheduled_date'      => $activity->scheduled_date,
                            'original_due_date'   => $activity->original_due_date,
                            'priority'            => $activity->priority,
                            'auto_priority'       => $activity->auto_priority ?? false,
                            'progress_percentage' => $activity->progress_percentage ?? 0,
                            'kanban_column_id'    => $activity->kanban_column_id,
                            'kanban_order'        => $activity->kanban_order,
                            'matrix_order'        => $activity->matrix_order,
                            'is_archived'         => $activity->is_archived ?? false,
                            'is_template'         => $activity->is_template ?? false,
                            'google_task_id'      => $activity->google_task_id,
                            'google_task_list_id' => $activity->google_task_list_id,
                            'google_calendar_event_id' => $activity->google_calendar_event_id,
                            'google_calendar_id'  => $activity->google_calendar_id,
                            'google_synced_at'    => $activity->google_synced_at,
                            'urgency'              => data_get($activity->metadata, 'urgency', 'medium'),
                            'cognitive_load'       => data_get($activity->metadata, 'cognitive_load', 1),
                            'is_out_of_skill_tree' => data_get($activity->metadata, 'is_out_of_skill_tree', false),
                            'autoprogram_settings' => data_get($activity->metadata, 'autoprogram_settings'),
                            'service_id'           => data_get($activity->metadata, 'service_id'),
                            'skill_id'             => data_get($activity->metadata, 'skill_id'),
                            'impact_human_metric'  => data_get($activity->metadata, 'impact_human_metric', 0),
                        ];

                        $task->fill($taskData);
                        $task->saveQuietly();

                        // Sincronizar asignaciones
                        $task->assignments()->delete();
                        foreach ($activity->assignments as $a) {
                            $task->assignments()->create([
                                'user_id' => $a->user_id,
                                'group_id' => $a->group_id,
                                'assigned_by_id' => $a->assigned_by_id ?? 1,
                                'assigned_at' => $a->assigned_at ?? now(),
                            ]);
                        }

                        // Sincronizar etiquetas
                        $task->tags()->delete();
                        foreach ($activity->tags as $t) {
                            $task->tags()->create([
                                'tag' => $t->tag,
                                'color_hex' => $t->color_hex ?? '#6b7280',
                            ]);
                        }
                    }
                }
            }
        } finally {
            static::$isSyncing = false;
        }
    }

    public function deleted(Activity $activity): void
    {
        if (static::$isSyncing || TaskObserver::$isSyncing) {
            return;
        }

        static::$isSyncing = true;

        try {
            $mapping = \DB::table('activity_task_mapping')
                ->where('activity_id', $activity->id)
                ->first();

            if ($mapping) {
                $task = Task::withTrashed()->find($mapping->task_id);
                if ($task) {
                    if ($activity->isForceDeleting()) {
                        $task->forceDelete();
                        \DB::table('activity_task_mapping')
                            ->where('activity_id', $activity->id)
                            ->delete();
                    } else {
                        $task->delete();
                    }
                }
            }
        } finally {
            static::$isSyncing = false;
        }
    }

    public function restored(Activity $activity): void
    {
        if (static::$isSyncing || TaskObserver::$isSyncing) {
            return;
        }

        static::$isSyncing = true;

        try {
            $mapping = \DB::table('activity_task_mapping')
                ->where('activity_id', $activity->id)
                ->first();

            if ($mapping) {
                $task = Task::onlyTrashed()->find($mapping->task_id);
                if ($task) {
                    $task->restore();
                }
            }
        } finally {
            static::$isSyncing = false;
        }
    }
}
