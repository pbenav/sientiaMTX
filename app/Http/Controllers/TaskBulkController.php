<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Traits\AwardsGamification;
use App\Traits\ManagesTaskDeletion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskBulkController extends Controller
{
    use AwardsGamification, ManagesTaskDeletion;

    /**
     * Update multiple tasks at once
     */
    public function bulkUpdate(Request $request, Team $team)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'field' => 'required|string|in:status,priority,assigned_user_id',
            'value' => 'required'
        ]);

        // Verificación individual de permisos por tarea
        $user = auth()->user();
        $validTaskIds = [];
        foreach ($request->task_ids as $taskId) {
            $task = Task::find($taskId);
            if ($task && $user->can('update', $task)) {
                $validTaskIds[] = $taskId;
            }
        }

        if (empty($validTaskIds)) {
            return back()->with('warning', 'No tienes permisos para actualizar las tareas seleccionadas.');
        }

        $taskService = app(\App\Services\TaskService::class);
        $result = $taskService->bulkUpdateTasks(
            $team, 
            $validTaskIds, 
            $request->field, 
            $request->value, 
            $user
        );

        // Gamification: Award points if status changed to completed
        foreach ($result['completedTasks'] as $completedTask) {
            $this->awardGamificationPoints($completedTask);
            $completedTask->notifyCoordinatorsIfCompleted();
        }

        return back()->with('success', "Se han actualizado {$result['count']} tareas correctamente.");
    }

    /**
     * Remove multiple tasks from storage
     */
    public function bulkDelete(Request $request, Team $team)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id'
        ]);

        $tasks = Task::whereIn('id', $request->task_ids)
            ->where('team_id', $team->id) // Security: Ensure tasks belong to the team
            ->get();
        $deletedCount = 0;

        foreach ($tasks as $task) {
            if ($request->user()->can('delete', $task)) {
                // Delete from Google Tasks if synced
                if ($task->google_task_id && auth()->user()->google_token) {
                    try {
                        $googleService = app(\App\Services\GoogleService::class);
                        $googleService->deleteTask($task->google_task_list_id, $task->google_task_id);
                    } catch (\Exception $e) {
                        Log::error('Bulk delete Google Task error: ' . $e->getMessage());
                    }
                }

                $task->delete();
                $deletedCount++;
            }
        }

        return redirect()->route('teams.tasks.index', $team)
            ->with('success', "$deletedCount tareas eliminadas correctamente.");
    }

    /**
     * Merge multiple selected tasks into a single target task (bulk operation).
     */
    public function bulkMerge(Request $request, Team $team)
    {
        $request->validate([
            'task_ids'       => 'required|array|min:2',
            'task_ids.*'     => 'exists:tasks,id',
            'target_task_id' => 'required|exists:tasks,id',
        ]);

        $targetTask = Task::findOrFail($request->input('target_task_id'));

        if ($targetTask->team_id !== $team->id) {
            return back()->with('warning', 'La tarea de destino debe pertenecer al mismo equipo.');
        }

        if (auth()->user()->cannot('update', $targetTask)) {
            return back()->with('warning', 'No tienes permisos para editar la tarea de destino.');
        }

        $sourceIds = collect($request->task_ids)->filter(fn($id) => (int)$id !== $targetTask->id);
        $sourceTasks = Task::whereIn('id', $sourceIds)->where('team_id', $team->id)->get();

        $merged = 0;
        $skipped = 0;

        foreach ($sourceTasks as $task) {
            if (auth()->user()->cannot('delete', $task)) {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($task, $targetTask) {
                // 1. Combine content additively
                $cleanSourceDesc = trim(strip_tags($task->description ?? ''));
                $cleanTargetDesc = trim(strip_tags($targetTask->description ?? ''));
                if ($cleanSourceDesc !== '' && strpos($cleanTargetDesc, $cleanSourceDesc) === false) {
                    $targetTask->description = ($targetTask->description ?? '') . "\n\n--- [Fusionado desde: {$task->title}] ---\n\n" . $task->description;
                }

                $cleanSourceObs = trim(strip_tags($task->observations ?? ''));
                $cleanTargetObs = trim(strip_tags($targetTask->observations ?? ''));
                if ($cleanSourceObs !== '' && strpos($cleanTargetObs, $cleanSourceObs) === false) {
                    $targetTask->observations = ($targetTask->observations ?? '') . "\n\n--- [Fusionado desde: {$task->title}] ---\n\n" . $task->observations;
                }
                $targetTask->save();

                // 2. Subtasks → target
                $task->children()->update(['parent_id' => $targetTask->id]);

                // 3. Time Logs
                $task->timeLogs()->update(['task_id' => $targetTask->id]);

                // 4. Attachments
                \App\Models\TaskAttachment::where('attachable_type', Task::class)
                    ->where('attachable_id', $task->id)
                    ->update(['attachable_id' => $targetTask->id]);
                \App\Models\TaskAttachment::where('attachable_type', 'App\Models\Task')
                    ->where('attachable_id', $task->id)
                    ->update(['attachable_id' => $targetTask->id]);

                // 5. Private Notes
                $task->privateNotes()->update(['task_id' => $targetTask->id]);

                // 6. Kudos
                \App\Models\Kudo::where('task_id', $task->id)->update(['task_id' => $targetTask->id]);

                // 7. History
                $task->histories()->update(['task_id' => $targetTask->id]);

                // 8. Tags (no duplicates)
                foreach ($task->tags as $tag) {
                    if (!$targetTask->tags()->where('tag', $tag->tag)->exists()) {
                        $tag->update(['task_id' => $targetTask->id]);
                    }
                }

                // 9. Assignments (no duplicates)
                foreach ($task->assignments as $assignment) {
                    $existsQuery = $targetTask->assignments();
                    $assignment->user_id
                        ? $existsQuery->where('user_id', $assignment->user_id)
                        : $existsQuery->where('group_id', $assignment->group_id);
                    if (!$existsQuery->exists()) {
                        $assignment->update(['task_id' => $targetTask->id]);
                    }
                }

                // 10. Forum Thread
                $sourceThread = $task->forumThread;
                if ($sourceThread) {
                    $targetThread = $targetTask->forumThread;
                    if ($targetThread) {
                        $sourceThread->messages()->update(['forum_thread_id' => $targetThread->id]);
                        $sourceThread->delete();
                    } else {
                        $sourceThread->update(['task_id' => $targetTask->id]);
                    }
                }

                // 11. Calendar Event
                $sourceCal = $task->calendarEvent;
                if ($sourceCal) {
                    if (!$targetTask->calendarEvent()->exists()) {
                        $sourceCal->update(['task_id' => $targetTask->id]);
                    } else {
                        $sourceCal->delete();
                    }
                }

                // 12. History trail on target + delete source
                $targetTask->histories()->create([
                    'user_id' => auth()->id(),
                    'action'  => 'task_merged',
                    'notes'   => "Tarea ID #{$task->id} ('{$task->title}') fusionada en bloque en esta tarea.",
                ]);

                $task->delete();
            });

            $merged++;
        }

        $msg = "Fusión completada: {$merged} tarea(s) fusionadas en «{$targetTask->title}»";
        if ($skipped > 0) {
            $msg .= " ({$skipped} omitidas por falta de permisos)";
        }

        return redirect()->route('teams.tasks.show', [$team, $targetTask])
            ->with('success', $msg . '.');
    }

    /**
     * Permanently remove all trashed tasks for this team.
     */
    public function purgeTrash(Request $request, Team $team)
    {
        // Only coordinators or global admins can purge
        if (!$team->isCoordinator(auth()->user()) && !auth()->user()->is_admin) {
            return redirect()->back()->with('warning', 'No tienes permisos para vaciar la papelera de este equipo.');
        }

        $trashedQuery = Task::onlyTrashed()->where('team_id', $team->id);
        $trashedCount = $trashedQuery->count();

        if ($trashedCount === 0) {
            return redirect()->back()->with('info', 'No hay tareas eliminadas para purgar.');
        }

        $tasks = $trashedQuery->get();

        /** @var \App\Models\Task $taskToPurge */
        foreach ($tasks as $taskToPurge) {
            $this->deepPurgeTask($taskToPurge);
        }

        return redirect()->back()->with('success', "Se han eliminado permanentemente $trashedCount tareas y sus registros asociados.");
    }
}
