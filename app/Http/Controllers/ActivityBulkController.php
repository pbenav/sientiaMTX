<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Services\ActivityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para operaciones masivas de actividades (actualización, eliminación, fusión).
 */
class ActivityBulkController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Actualiza masivamente un campo específico de múltiples actividades.
     *
     * Valida que el usuario tenga permisos de actualización sobre cada actividad seleccionada.
     * Los campos soportados son: status (vía ActivityService::changeStatus), priority, assigned_user_id.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener task_ids (array), field (status|priority|assigned_user_id), value
     * @param  \App\Models\Team  $team  Equipo al que pertenecen las actividades
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito o advertencia
     */
    public function bulkUpdate(Request $request, Team $team)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:activities,id',
            'field' => 'required|string|in:status,priority,assigned_user_id',
            'value' => 'required'
        ]);

        $user = auth()->user();
        $validActivityIds = [];
        
        foreach ($request->task_ids as $activityId) {
            $activity = Activity::find($activityId);
            if ($activity && $user->can('update', $activity)) {
                $validActivityIds[] = $activityId;
            }
        }

        if (empty($validActivityIds)) {
            return back()->with('warning', 'No tienes permisos para actualizar las actividades seleccionadas.');
        }

        // We do bulk update manually here since ActivityService might not have it yet
        $count = 0;
        foreach ($validActivityIds as $id) {
            $activity = Activity::find($id);
            if ($request->field === 'status') {
                $this->activityService->changeStatus($activity, $request->value);
                $count++;
            } elseif ($request->field === 'priority') {
                $activity->update(['priority' => $request->value]);
                $count++;
            } elseif ($request->field === 'assigned_user_id') {
                $activity->assignments()->whereNotNull('user_id')->delete(); // Clear old user assignments
                if ($request->value) {
                    $activity->assignments()->create([
                        'user_id' => $request->value,
                        'assigned_by_id' => $user->id,
                        'assigned_at' => now(),
                    ]);
                }
                $count++;
            }
        }

        return back()->with('success', "Se han actualizado {$count} actividades correctamente.");
    }

    /**
     * Elimina masivamente actividades verificando los permisos del usuario.
     *
     * Usa ActivityService::delete() para garantizar una eliminación limpia
     * con registro en historial y limpieza de dependencias.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener task_ids (array)
     * @param  \App\Models\Team  $team  Equipo al que pertenecen las actividades
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito
     */
    public function bulkDelete(Request $request, Team $team)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:activities,id'
        ]);

        $activities = Activity::whereIn('id', $request->task_ids)
            ->where('team_id', $team->id)
            ->get();
            
        $deletedCount = 0;

        foreach ($activities as $activity) {
            if ($request->user()->can('delete', $activity)) {
                $this->activityService->delete($activity);
                $deletedCount++;
            }
        }

        return redirect()->route('teams.activities.index', $team)
            ->with('success', "$deletedCount actividades eliminadas correctamente.");
    }

    /**
     * Fusiona múltiples actividades fuente en una actividad destino.
     *
     * Combina aditivamente descripciones y observaciones (evitando duplicados),
     * reasigna subtareas, time logs, attachments, notes, kudos, history, tags y
     * assignments a la tarea destino. Registra un historial de fusión y elimina
     * las actividades fuente.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener task_ids (array >= 2), target_task_id
     * @param  \App\Models\Team  $team  Equipo al que pertenecen las actividades
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito/advertencia
     */
    public function bulkMerge(Request $request, Team $team)
    {
        $request->validate([
            'task_ids'       => 'required|array|min:2',
            'task_ids.*'     => 'exists:activities,id',
            'target_task_id' => 'required|exists:activities,id',
        ]);

        $targetTask = Activity::findOrFail($request->input('target_task_id'));

        if ($targetTask->team_id !== $team->id) {
            return back()->with('warning', 'La actividad de destino debe pertenecer al mismo equipo.');
        }

        if (auth()->user()->cannot('update', $targetTask)) {
            return back()->with('warning', 'No tienes permisos para editar la actividad de destino.');
        }

        $sourceIds = collect($request->task_ids)->filter(fn($id) => (int)$id !== $targetTask->id);
        $sourceTasks = Activity::whereIn('id', $sourceIds)->where('team_id', $team->id)->get();

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
                \App\Models\ActivityAttachment::where('activity_id', $task->id)
                    ->update(['activity_id' => $targetTask->id]);

                // 5. Notes
                $task->notes()->update(['activity_id' => $targetTask->id]);

                // 6. Kudos (if applicable to activities)
                \App\Models\Kudo::where('task_id', $task->id)->update(['task_id' => $targetTask->id]);

                // 7. History
                $task->histories()->update(['activity_id' => $targetTask->id]);

                // 8. Tags (no duplicates)
                foreach ($task->tags as $tag) {
                    if (!$targetTask->tags()->where('tag', $tag->tag)->exists()) {
                        $tag->update(['activity_id' => $targetTask->id]);
                    }
                }

                // 9. Assignments (no duplicates)
                foreach ($task->assignments as $assignment) {
                    $existsQuery = $targetTask->assignments();
                    $assignment->user_id
                        ? $existsQuery->where('user_id', $assignment->user_id)
                        : $existsQuery->where('group_id', $assignment->group_id);
                    if (!$existsQuery->exists()) {
                        $assignment->update(['activity_id' => $targetTask->id]);
                    }
                }

                // 12. History trail on target + delete source
                $targetTask->histories()->create([
                    'user_id' => auth()->id(),
                    'action'  => 'task_merged',
                    'notes'   => "Actividad ID #{$task->id} ('{$task->title}') fusionada en bloque en esta actividad.",
                ]);

                $this->activityService->delete($task);
            });

            $merged++;
        }

        $msg = "Fusión completada: {$merged} actividad(es) fusionadas en «{$targetTask->title}»";
        if ($skipped > 0) {
            $msg .= " ({$skipped} omitidas por falta de permisos)";
        }

        return redirect()->route('teams.activities.show', [$team, $targetTask])
            ->with('success', $msg . '.');
    }
}
