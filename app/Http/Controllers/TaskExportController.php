<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TaskExportController extends Controller
{
    public function copyToTeam(Request $request, Team $team, Task $task)
    {
        $request->validate([
            'target_team_id' => 'required|exists:teams,id'
        ]);

        $user = auth()->user();
        if ($user->cannot('view', $team) || $task->team_id !== $team->id) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $targetTeam = Team::find($request->target_team_id);
        if ($user->cannot('view', $targetTeam)) {
            return response()->json(['success' => false, 'message' => 'No tienes acceso al equipo de destino.'], 403);
        }

        // Use the unified creation logic (simulating an export/import flow)
        $newTask = DB::transaction(function () use ($task, $targetTeam, $user) {
            // 1. "Export" the current task to an array
            $taskData = [
                'title' => $task->title,
                'description' => $task->description,
                'observations' => $task->observations,
                'priority' => $task->priority,
                'urgency' => $task->urgency,
                'visibility' => $task->visibility,
                'is_template' => false, // Force false on reproduction for better UX as discussed
                'cognitive_load' => $task->cognitive_load,
                'is_backstage' => $task->is_backstage,
                'autoprogram_settings' => $task->autoprogram_settings,
                'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
                'skills' => $task->skills->map(fn($s) => ['name' => $s->name, 'category' => $s->category])->toArray(),
                'tags' => $task->tags->map(fn($t) => ['tag' => $t->tag, 'color_hex' => $t->color_hex])->toArray(),
            ];

            // 2. "Import" it into the target team
            $cloned = $this->createTaskFromData($targetTeam, $taskData);
            
            // 3. Additional reproduction-specific adjustments
            $cloned->assigned_user_id = $user->id; 
            $cloned->saveQuietly();

            // 4. Create History Record
            $cloned->histories()->create([
                'user_id' => $user->id,
                'action' => 'cloned',
                'notes' => 'Reproducida desde el equipo: ' . $task->team->name
            ]);

            return $cloned;
        });

        return response()->json([
            'success' => true,
            'message' => __('tasks.cloned_success', ['team' => $targetTeam->name]),
            'url' => route('teams.tasks.show', [$targetTeam, $newTask])
        ]);
    }

    public function cloneTask(Request $request, Team $team, Task $task)
    {
        $user = auth()->user();
        if ($user->cannot('view', $team) || $task->team_id !== $team->id) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($user->cannot('create', [Task::class, $team])) {
            return redirect()->back()->with('warning', 'No tienes permisos para crear tareas.');
        }

        $clonedTask = DB::transaction(function () use ($task, $team, $user) {
            // 1. Create the base cloned task
            $newTitle = '[Clon] ' . $task->title;
            if (mb_strlen($newTitle) > 255) {
                $newTitle = mb_substr($newTitle, 0, 252) . '...';
            }

            $new = $team->tasks()->create([
                'title' => $newTitle,
                'description' => $task->description,
                'priority' => $task->priority,
                'urgency' => $task->urgency,
                'status' => 'pending',
                'progress_percentage' => 0,
                'scheduled_date' => $task->scheduled_date,
                'due_date' => $task->due_date,
                'original_due_date' => $task->due_date,
                'created_by_id' => $user->id,
                'observations' => $task->observations,
                'parent_id' => $task->parent_id,
                'is_template' => $task->is_template,
                'visibility' => $task->visibility,
                'is_autoprogrammable' => $task->is_autoprogrammable,
                'autoprogram_settings' => $task->autoprogram_settings,
                'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
                'cognitive_load' => $task->cognitive_load,
                'is_backstage' => $task->is_backstage,
                'service_id' => $task->service_id,
                'expediente_id' => $task->expediente_id,
                'is_timeline_locked' => $task->is_timeline_locked,
            ]);

            // Sync Kanban Column
            $new->syncKanbanColumn();

            // 2. Sync Skills
            if ($task->skills->isNotEmpty()) {
                $new->skills()->sync($task->skills->pluck('id')->toArray());
            }

            // 3. Sync Tags (if they exist)
            if ($task->tags && $task->tags->isNotEmpty()) {
                $new->tags()->sync($task->tags->pluck('id')->toArray());
            }

            // 4. Sync Assigned Users & Groups
            if ($task->assignedTo->isNotEmpty()) {
                $new->assignedTo()->syncWithPivotValues($task->assignedTo->pluck('id')->toArray(), ['assigned_by_id' => $user->id]);
            }
            if ($task->assignedGroups->isNotEmpty()) {
                $new->assignedGroups()->syncWithPivotValues($task->assignedGroups->pluck('id')->toArray(), ['assigned_by_id' => $user->id]);
            }

            // Create history record
            $new->histories()->create([
                'user_id' => $user->id,
                'action' => 'cloned',
                'notes' => 'Clonado desde la tarea ID: ' . $task->id
            ]);

            return $new;
        });

        return redirect()->route('teams.tasks.edit', [$team, $clonedTask])->with('success', 'Tarea clonada con éxito: "' . $clonedTask->title . '"');
    }

    public function importJson(Request $request, Team $team)
    {
        if (auth()->user()->cannot('create', [Task::class, $team])) {
            return response()->json(['success' => false, 'message' => __('No tienes permisos para crear tareas en este equipo.')], 403);
        }
        $request->validate([
            'file' => 'required_without:json_content|file|mimes:json',
            'json_content' => 'required_without:file|string|nullable'
        ]);

        if ($request->hasFile('file')) {
            $json = file_get_contents($request->file('file')->getRealPath());
        } else {
            $json = $request->json_content;
        }

        $data = json_decode($json, true);
        if (!$data || ($data['type'] ?? '') !== 'sientia_task_v1') {
            Log::warning('JSON Import Error: ' . json_last_error_msg() . ' / JSON String: ' . $json);
            return response()->json(['success' => false, 'message' => 'Formato de datos JSON inválido.'], 422);
        }

        $task = $this->createTaskFromData($team, $data['task']);

        return response()->json(['success' => true, 'message' => 'Tarea importada correctamente.', 'url' => route('teams.tasks.show', [$team, $task])]);
    }

    public function exportJson(Request $request, Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) {
            abort(404);
        }
        $this->authorize('view', $task);

        $data = [
            'type' => 'sientia_task_v1',
            'exported_at' => now()->toDateTimeString(),
            'task' => [
                'title' => $task->title,
                'description' => $task->description,
                'observations' => $task->observations,
                'priority' => $task->priority,
                'urgency' => $task->urgency,
                'visibility' => $task->visibility,
                'is_template' => $task->is_template,
                'cognitive_load' => $task->cognitive_load,
                'is_backstage' => $task->is_backstage,
                'autoprogram_settings' => $task->autoprogram_settings,
                'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
                'skills' => $task->skills->map(fn($s) => ['name' => $s->name, 'category' => $s->category])->toArray(),
                'tags' => $task->tags->map(fn($t) => ['tag' => $t->tag, 'color_hex' => $t->color_hex])->toArray(),
            ]
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($data);
        }

        $filename = 'task-' . Str::slug($task->title) . '-' . date('YmdHis') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    private function createTaskFromData(Team $team, array $taskData): Task
    {
        $task = $team->tasks()->create([
            'title' => $taskData['title'],
            'description' => $taskData['description'] ?? null,
            'observations' => $taskData['observations'] ?? null,
            'priority' => $taskData['priority'] ?? 'medium',
            'urgency' => $taskData['urgency'] ?? 'medium',
            'visibility' => $taskData['visibility'] ?? 'private',
            'is_template' => $taskData['is_template'] ?? false,
            'cognitive_load' => $taskData['cognitive_load'] ?? 1,
            'is_backstage' => $taskData['is_backstage'] ?? false,
            'autoprogram_settings' => $taskData['autoprogram_settings'] ?? null,
            'is_out_of_skill_tree' => $taskData['is_out_of_skill_tree'] ?? false,
            'created_by_id' => auth()->id(),
            'status' => 'pending',
            'progress_percentage' => 0,
            'kanban_order' => 0,
            'nudge_count' => 0,
        ]);

        // 1. Sync Skills by Name
        if (!empty($taskData['skills'])) {
            $skillNames = array_column($taskData['skills'], 'name');
            $skillIds = \App\Models\Skill::forTeamOrGlobal($team->id)
                ->whereIn('name', $skillNames)
                ->pluck('id');
            $task->skills()->sync($skillIds);
        }

        // 2. Sync Tags
        if (!empty($taskData['tags'])) {
            foreach ($taskData['tags'] as $tagData) {
                $task->tags()->create([
                    'tag' => $tagData['tag'],
                    'color_hex' => $tagData['color_hex'] ?? '#6366f1',
                ]);
            }
        }

        // 3. Initial Kanban Sync
        $task->syncKanbanColumn();

        return $task;
    }
}
