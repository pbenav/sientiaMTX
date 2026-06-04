<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Str;

class TaskObserver
{
    /**
     * Handle the Task "creating" event.
     */
    public function creating(Task $task): void
    {
        if (empty($task->uuid)) {
            $task->uuid = (string) Str::uuid();
        }
    }

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

        // Cascade completion: If this task is completed, all children should be completed (except for Master Plans / Distributed Templates)
        if ($task->isDirty('status') && $task->status === 'completed' && !$task->is_template) {
            $task->children()->where('status', '!=', 'completed')->each(function($child) {
                $child->update(['status' => 'completed', 'progress_percentage' => 100]);
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
}
