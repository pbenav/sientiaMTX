<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Traits\AwardsGamification;

class TaskActionController extends Controller
{
    use AwardsGamification;

    /**
     * Move task to a different quadrant (Ajax)
     */
    public function move(Request $request, Team $team, Task $task)
    {
        $this->authorize('update', $task);

        if ($task->is_timeline_locked && ($request->has('scheduled_date') || $request->has('due_date'))) {
            return response()->json([
                'success' => false,
                'error' => 'Esta tarea tiene la programación bloqueada (inamovible) y no se puede reprogramar.'
            ], 422);
        }

        $validated = $request->validate([
            'quadrant' => 'nullable|integer|between:1,4',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled,blocked',
            'progress_percentage' => 'nullable|integer|between:0,100',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'is_archived' => 'nullable|boolean',
            'assigned_user_id' => 'nullable|exists:users,id',
            'matrix_order' => 'nullable|integer|min:0',
            'full_order' => 'nullable|array',
            'full_order.*' => 'integer|exists:tasks,id',
        ]);

        $oldStatus = $task->status;
        \Log::info('Task move request:', ['task_id' => $task->id, 'data' => $request->all()]);

        // Collect all changes in the model object first
        if ($request->has('scheduled_date')) $task->scheduled_date = $validated['scheduled_date'];
        if ($request->has('due_date')) $task->due_date = $validated['due_date'];
        if ($request->has('progress_percentage')) {
            $task->progress_percentage = $validated['progress_percentage'];
            
            if (!$request->has('status')) {
                if ($task->progress_percentage == 100 && $oldStatus !== 'completed' && $oldStatus !== 'cancelled') {
                    $task->status = 'completed';
                    $this->awardGamificationPoints($task);
                    $task->notifyCoordinatorsIfCompleted();
                } elseif ($task->progress_percentage == 0 && $oldStatus !== 'pending') {
                    $task->status = 'pending';
                } elseif ($task->progress_percentage > 0 && $task->progress_percentage < 100 && in_array($oldStatus, ['completed', 'pending'])) {
                    $task->status = 'in_progress';
                }
            }
        }
        if ($request->has('is_archived')) {
            $task->is_archived = (bool) $validated['is_archived'];
            \Log::info('Setting is_archived to:', ['val' => $task->is_archived]);
        }
        if ($request->has('assigned_user_id')) {
            $task->assigned_user_id = $validated['assigned_user_id'];
        }
        
        if ($request->has('status')) {
            $task->status = $validated['status'];
            
            if ($task->status === 'completed') {
                $task->progress_percentage = 100;
            } elseif (in_array($task->status, ['pending', 'in_progress', 'blocked']) && $task->progress_percentage === 100) {
                $task->progress_percentage = 90;
            }

            // Automatic de-completion for parents
            if ($task->isInstance() && $oldStatus === 'completed' && $task->status !== 'completed') {
                $parent = $task->parent;
                if ($parent && $parent->status === 'completed') {
                    $parent->update(['status' => 'in_progress']);
                }
            }

            // Gamification: Award points if newly completed via move
            if ($task->status === 'completed' && $oldStatus !== 'completed') {
                $this->awardGamificationPoints($task);
            }
        }

        if ($request->has('quadrant') && $validated['quadrant'] !== null) {
            $mapping = [
                1 => ['priority' => 'high', 'urgency' => 'high'],
                2 => ['priority' => 'high', 'urgency' => 'low'],
                3 => ['priority' => 'low', 'urgency' => 'high'],
                4 => ['priority' => 'low', 'urgency' => 'low'],
            ];
            $task->priority = $mapping[$validated['quadrant']]['priority'];
            $task->urgency = $mapping[$validated['quadrant']]['urgency'];
            
            // If it was a template, keep it as is, but if it was a child/instance, it's always in_progress when moved
            if (!$task->is_template && !in_array($task->status, ['completed', 'cancelled'])) {
                $task->status = 'in_progress';
            }
        }

        if ($request->has('matrix_order')) {
            $task->matrix_order = $validated['matrix_order'];
        }

        // Final save for the main task
        $task->save();

        // Handle bulk reordering if full_order is provided
        if ($request->has('full_order') && is_array($request->full_order)) {
            $fullOrder = $request->full_order;
            // Use a transaction for bulk updates to ensure atomicity and speed
            \Illuminate\Support\Facades\DB::transaction(function() use ($fullOrder, $team) {
                foreach ($fullOrder as $index => $id) {
                    \App\Models\Task::where('id', $id)
                        ->where('team_id', $team->id)
                        ->update(['matrix_order' => $index]);
                }
            });
        }

        // Secondary Effects (Notifications & Syncs)
        if ($task->is_template && ($request->has('scheduled_date') || $request->has('due_date'))) {
            $task->instances()->update([
                'scheduled_date' => $task->scheduled_date,
                'due_date' => $task->due_date
            ]);
        }

        if ($request->has('status') && $task->status === 'blocked' && $oldStatus !== 'blocked') {
            $team->creator->notify(new \App\Notifications\TaskBlockedNotification($task, auth()->user()));
            foreach ($team->members()->wherePivotIn('role_id', function ($q) {
                $q->select('id')->from('team_roles')->where('name', 'coordinator');
            })->get() as $coordinator) {
                if ($coordinator->id !== auth()->id()) {
                    $coordinator->notify(new \App\Notifications\TaskBlockedNotification($task, auth()->user()));
                }
            }
        }

        if ($task->isInstance() && ($request->has('status') || $request->has('progress_percentage'))) {
            $currentParent = $task->parent;
            while ($currentParent) {
                // For template tasks, we must update the progress_percentage column 
                // so that queries/scopes that don't use the attribute still work.
                $currentParent->update(['progress_percentage' => $currentParent->progress]);
                $currentParent->syncKanbanColumn(); // Update its column if needed
                $currentParent = $currentParent->parent;
            }
            $task->refresh();
        }

        \Log::info("Task Team ID: " . $task->team_id . " | Team is null: " . ($task->team === null ? "yes" : "no"));
        $task->syncKanbanColumn();

        return response()->json([
            'success' => true,
            'task_status' => $task->status,
            'task_progress' => $task->progress_percentage,
            'kanban_column_id' => $task->kanban_column_id,
            'parent_progress' => $task->parent_id ? $task->parent->progress_percentage : null
        ]);
    }

    /**
     * Nudge a user assigned to a task instance
     */
    public function nudge(Request $request, Team $team, Task $task)
    {
        $this->authorize('view', $team);

        $type = 'collaborative';
        $progress = $task->progress;

        if ($task->status === 'blocked') {
            $type = 'unblocking';
        } elseif ($task->due_date && $task->due_date->isFuture() && $task->due_date->diffInHours(now()) < 24) {
            $type = 'deadline';
        }

        $recipientId = $request->input('user_id');
        $recipient = $recipientId ? \App\Models\User::find($recipientId) : ($task->assignedUser ?: $task->creator);

        if (!$recipient) {
            return response()->json([
                'success' => false, 
                'message' => 'No hay ningún usuario asociado a esta tarea para notificar.'
            ], 400);
        }

        $customMessage = $request->input('custom_message');
        
        $recipient->notify(new \App\Notifications\TaskNudgeNotification($task, $type, $progress, $customMessage));

        $task->increment('nudge_count');
        $task->refresh();

        return response()->json([
            'success' => true, 
            'message' => __('tasks.nudge_sent'),
            'nudge_count' => $task->nudge_count
        ]);
    }

    /**
     * Bulk nudge multiple task instances
     */
    public function bulkNudge(Request $request, Team $team)
    {
        $this->authorize('view', $team);

        $validated = $request->validate([
            'task_ids'      => 'nullable|array',
            'task_ids.*'    => 'exists:tasks,id',
            'targets'       => 'nullable|array',
            'targets.*'     => 'string',
            'custom_message'=> 'nullable|string|max:500'
        ]);

        $targets = $request->input('targets', []);
        $taskIds = $request->input('task_ids', []);

        // Convertir todo a una lista de pares ['task_id' => ..., 'user_id' => ...]
        $items = [];
        $seen = [];
        foreach ($targets as $target) {
            $parts = explode(':', $target);
            if (!empty($parts[0])) {
                $tId = $parts[0];
                $uId = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;
                $key = $tId . '_' . ($uId ?: 'none');
                if (!isset($seen[$key])) {
                    $items[] = ['task_id' => $tId, 'user_id' => $uId];
                    $seen[$key] = true;
                    $seen[$tId . '_none'] = true;
                }
            }
        }
        foreach ($taskIds as $tId) {
            $uId = $request->input('user_id');
            $key = $tId . '_' . ($uId ?: 'none');
            if (!isset($seen[$key])) {
                $items[] = ['task_id' => $tId, 'user_id' => $uId];
                $seen[$key] = true;
            }
        }

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'No se han especificado tareas para notificar.'
            ], 400);
        }

        $sent    = 0;
        $failed  = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $task = Task::where('id', $item['task_id'])->where('team_id', $team->id)->first();
            if (!$task) {
                $skipped++;
                continue;
            }

            $type      = 'collaborative';
            $progress  = $task->progress;

            if ($task->status === 'blocked') {
                $type = 'unblocking';
            } elseif ($task->due_date && $task->due_date->isFuture() && $task->due_date->diffInHours(now()) < 24) {
                $type = 'deadline';
            }

            $recipientId = $item['user_id'];
            $recipient   = $recipientId
                ? \App\Models\User::find($recipientId)
                : ($task->assignedUser ?: ($task->assignedTo->first() ?: $task->creator));

            if (!$recipient) {
                $skipped++;
                continue;
            }

            try {
                $recipient->notify(new \App\Notifications\TaskNudgeNotification(
                    $task, $type, $progress, $validated['custom_message'] ?? null
                ));
                $task->increment('nudge_count');

                // Auditoría: registrar en el historial de la tarea
                $task->histories()->create([
                    'user_id' => auth()->id(),
                    'action'  => 'bulk_nudge_sent',
                    'notes'   => sprintf(
                        'Recordatorio masivo enviado a %s%s',
                        $recipient->name,
                        !empty($validated['custom_message'])
                            ? ' — Mensaje: "' . $validated['custom_message'] . '"'
                            : ''
                    ),
                ]);

                $sent++;
            } catch (\Exception $e) {
                \Log::error("bulkNudge: fallo al notificar usuario #{$recipient->id} en tarea #{$task->id}: " . $e->getMessage());
                $failed++;
            }
        }

        // Construir mensaje de respuesta claro
        $parts = [];
        if ($sent)    $parts[] = "{$sent} enviado(s)";
        if ($skipped) $parts[] = "{$skipped} sin destinatario";
        if ($failed)  $parts[] = "{$failed} fallido(s) (ver logs)";

        return response()->json([
            'success' => $sent > 0,
            'sent'    => $sent,
            'failed'  => $failed,
            'skipped' => $skipped,
            'message' => implode(', ', $parts) ?: 'No se procesó ningún recordatorio.',
        ], $failed > 0 && $sent === 0 ? 500 : 200);
    }

    /**
     * Store a user's rating for the quality of this task.
     */
    public function rate(Request $request, Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) abort(404);

        $user = auth()->user();
        
        $isAssigned = $task->assignedTo()->where('users.id', $user->id)->exists() 
                   || $task->assigned_user_id === $user->id;
                   
        if (!$isAssigned && !$team->isManager($user)) {
            return response()->json(['message' => 'Solo los usuarios asignados pueden valorar esta tarea.'], 403);
        }

        $request->validate([
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255'
        ]);

        $rating = $task->ratings()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'score' => $request->score,
                'comment' => $request->comment
            ]
        );

        $task->updateQualityCache();

        if ($rating->score >= 4 && $task->creator && $task->creator->id !== $user->id) {
             try {
                 $task->creator->notify(new \App\Notifications\TaskQualityVotedNotification($task, $user, $rating->score));
             } catch (\Exception $e) {
                 \Log::error("Failed sending task quality notification: " . $e->getMessage());
             }
        }

        return response()->json([
            'success' => true,
            'avg_score' => $task->avg_quality_score,
            'message' => __('¡Valoración registrada con éxito!')
        ]);
    }

    public function toggleAutoPriority(Team $team, Task $task)
    {
        \Log::info("Toggle AutoPriority Attempt: Task #{$task->id} by User #" . auth()->id());
        
        try {
            $this->authorize('update', $task);
            
            $task->auto_priority = !$task->auto_priority;
            $task->save();

            if ($task->auto_priority) {
                $task->updateAutoPriority();
            }

            \Log::info("Toggle AutoPriority SUCCESS: Task #{$task->id} is now " . ($task->auto_priority ? 'ON' : 'OFF'));

            return response()->json([
                'success' => true,
                'auto_priority' => $task->auto_priority,
                'priority' => $task->priority,
                'priority_label' => __('tasks.priorities.' . $task->priority)
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::error("Toggle AutoPriority AUTH FAILED: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        } catch (\Exception $e) {
            \Log::error("Toggle AutoPriority CRITICAL ERROR: " . $e->getMessage(), [
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
