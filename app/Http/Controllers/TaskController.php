<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Models\TaskAttachment;
use App\Models\AttachmentLog;
use App\Traits\HandlesEisenhowerMatrix;
use App\Traits\AwardsGamification;
use App\Traits\ManagesTaskDeletion;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskEventNotification;
use Illuminate\Http\Request;

use App\Traits\HandlesPersistentFilters;

class TaskController extends Controller
{
    use HandlesEisenhowerMatrix, AwardsGamification, ManagesTaskDeletion, HandlesPersistentFilters;

    public function index(Request $request, Team $team)
    {
        return redirect()->route('teams.activities.index', $team);
        $user = auth()->user();
        $isManager = $team->isManager($user);
        
        $query = $team->tasks()
            ->visibleTo($user, $isManager)
            ->operationalFor($user, $team, true)
            ->with([
                'assignedUser', 
                'tags', 
                'creator', 
                'parent', 
                'expediente',
                'children' => function($q) use ($user, $isManager) {
                    $q->visibleTo($user, $isManager);
                }
            ]);

        // --- Filters ---
        $filters = $this->getPersistentFilters($request, 'tasks', [
            'status', 'priority', 'assigned_to', 'skill_id', 'type', 'search', 'per_page'
        ]);

        if ($filters['status'] ?? null) {
            if ($filters['status'] === 'trashed') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if ($filters['priority'] ?? null) {
            $query->where('priority', $filters['priority']);
        }

        if ($filters['assigned_to'] ?? null) {
            $query->where('assigned_user_id', $filters['assigned_to']);
        }

        if ($filters['skill_id'] ?? null) {
            $skillId = $filters['skill_id'];
            $query->where(function ($q) use ($skillId) {
                $q->where('skill_id', $skillId)
                  ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
            });
        }

        if ($filters['type'] ?? null) {
            if ($filters['type'] === 'template') {
                $query->where('is_template', true);
            } elseif ($filters['type'] === 'instance') {
                $query->where('is_template', false)->whereNotNull('parent_id');
            } elseif ($filters['type'] === 'plain') {
                $query->where('is_template', false)->whereNull('parent_id');
            }
        }

        if ($filters['search'] ?? null) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('parent', function($pq) use ($searchTerm) {
                      $pq->where('title', 'like', '%' . $searchTerm . '%');
                  });
            });

            // Also filter the children relationship so only matched subtasks are shown in the nested view
            $query->with(['children' => function($q) use ($searchTerm, $user, $isManager) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->visibleTo($user, $isManager);
            }]);
        }

        // --- Sorting ---
        $sort = $request->get('sort');
        $direction = $request->get('direction', 'asc');
        
        $allowedSorts = ['title', 'status', 'priority', 'due_date', 'created_at', 'progress_percentage'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        } else {
            // Default sort: Priority (Critical -> Low), Status (Pending -> Others), and Progress (High -> Low)
            $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
                  ->orderByRaw("FIELD(status, 'pending', 'blocked', 'in_progress', 'completed', 'cancelled') ASC")
                  ->orderBy('progress_percentage', 'desc');
        }

        // --- Hide completed filter (session-based preference) ---
        if (session('hide_completed_tasks', true) && !$filters['status']) {
            $query->whereNotIn('status', ['completed', 'cancelled']);
        }

        // --- Pagination ---
        $perPage = $filters['per_page'] ?? 10;
        if (!in_array($perPage, [10, 25, 50, 100, 'all'])) {
            $perPage = 10;
        }

        if ($perPage === 'all') {
            // Secure "all" fetch
            $tasks = $query->paginate($query->count())->withQueryString();
        } else {
            $tasks = $query->paginate($perPage)->withQueryString();
        }
        $members = $team->members;
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->get();
        $hideCompleted = session('hide_completed_tasks', true);

        $services = $team->services()->with(['reports' => function($q) {
            $q->latest()->limit(5);
        }])->get();

        return view('tasks.index', compact('team', 'tasks', 'members', 'hideCompleted', 'skills', 'services', 'filters'));
    }

    /**
     * Show the form for creating a new task
     */
    public function create(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $allMembers = $team->members; // All members — for owner selector
        // Exclude the current user from assignee list: creator is implicit owner
        // Allow the current user to be assigned as well so they can generate instances for themselves if they wish
        $users = $team->members;
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $tasks = $team->tasks()->with('assignedUser')->orderBy('title')->get();
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
        $services = $team->services()->orderBy('name')->get();

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            if (!str_contains($referer, "/tasks/create")) {
                session()->put("back_url_task_create_{$team->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_create_{$team->id}", route('teams.dashboard', $team));

        $expedientes = $team->expedientes()->orderBy('code', 'desc')->get();
        $selectedExpedienteId = request('expediente_id');

        return view('tasks.create', compact('team', 'users', 'allMembers', 'groups', 'priorities', 'tasks', 'backUrl', 'skills', 'services', 'expedientes', 'selectedExpedienteId'));
    }

    /**
     * Store a newly created task in storage
     */
    public function store(\App\Http\Requests\StoreTaskRequest $request, Team $team)
    {
        $validated = $request->validated();

        // Quota check before hitting the service
        if ($request->hasFile('attachments')) {
            $totalUploadSize = collect($request->file('attachments'))->sum(fn($file) => $file->getSize());
            if (!$team->hasAvailableQuota($totalUploadSize)) {
                return back()->withInput()->withErrors(['attachments' => '⚠️ El equipo ha alcanzado su límite de almacenamiento. Libera espacio para subir más archivos.']);
            }
        }

        $taskService = app(\App\Services\TaskService::class);
        $task = $taskService->createTask(
            $team, 
            $validated, 
            $request->file('attachments'), 
            $request->input('drive_attachments')
        );

        return redirect()->route('teams.tasks.show', [$team, $task])
            ->with('success', __('tasks.created'));
    }

    /**
     * Display the specified task
     */
    public function show(Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('dashboard')->with('warning', __('teams.unauthorized_access'));
        }

        if (auth()->user()->cannot('view', $task)) {
            return redirect()->route('teams.tasks.index', $team)->with('warning', 'Acceso prohibido: La tarea no está accesible o es privada.');
        }

        $task->load(['assignedTo', 'assignedGroups', 'creator', 'histories', 'tags', 'attachments', 'attachments.logs.user']);

        // Load parent attachments if it's an instance or has a parent
        if ($task->parent_id) {
            $task->load('parent.attachments');
        }

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            // Only update the back url if we are not coming from the same task
            if (!str_contains($referer, "/tasks/{$task->id}")) {
                session()->put("back_url_task_{$task->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_{$task->id}", route('teams.dashboard', $team));

        return view('tasks.show', compact('team', 'task', 'backUrl'));
    }

    /**
     * Show the form for editing the task
     */
    public function edit(Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('dashboard')->with('warning', __('teams.unauthorized_access'));
        }

        if (auth()->user()->cannot('update', $task)) {
            return redirect()->route('teams.tasks.index', $team)
                ->with('warning', 'Acceso prohibido: No tienes permisos para modificar esta tarea privada.');
        }

        $task->load('attachments');
        $allMembers = $team->members; // All members — for owner selector
        // Allow the current user to be assigned as well so they can generate instances for themselves if they wish
        $users = $team->members;
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $statuses = ['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada', 'blocked' => 'Bloqueada'];
        $tasks = $team->tasks()->with('assignedUser')->where('id', '!=', $task->id)->orderBy('title')->get();
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
        $services = $team->services()->orderBy('name')->get();

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            if (!str_contains($referer, "/tasks/{$task->id}/edit")) {
                session()->put("back_url_task_edit_{$task->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_edit_{$task->id}", route('teams.tasks.show', [$team, $task]));

        $services = $team->services()->orderBy('name')->get();
        $expedientes = $team->expedientes()->orderBy('code', 'desc')->get();

        return view('tasks.edit', compact('team', 'task', 'users', 'allMembers', 'groups', 'priorities', 'statuses', 'tasks', 'backUrl', 'skills', 'services', 'expedientes'));
    }

    /**
     * Update the task in storage
     */
    public function update(\App\Http\Requests\UpdateTaskRequest $request, Team $team, Task $task)
    {
        $validated = $request->validated();

        // Store old values for history
        $oldValues = $task->getAttributes();
        $statusChanged = $task->status !== ($validated['status'] ?? $task->status);

        // La auto-publicación ha sido eliminada.
        // Las tareas mantienen su visibilidad definida sin importar los colaboradores asignados.
        $autoPublic = false;
        $visibility = $validated['visibility'] ?? $task->visibility;
        $validated['visibility'] = $visibility;


        // Store old status for gamification check
        $oldStatus = $oldValues['status'] ?? null;
        $isCoordinator = $team->isCoordinator(auth()->user()) || auth()->id() === $task->created_by_id;

        $taskService = app(\App\Services\TaskService::class);
        $task = $taskService->updateTask(
            $task,
            $team,
            $validated,
            $request->all(),
            $isCoordinator
        );

        // Gamification: Award points if completed
        if ($task->status === 'completed' && $oldStatus !== 'completed') {
            $this->awardGamificationPoints($task);
            $task->notifyCoordinatorsIfCompleted();
        }

        // Notification for Blocked status
        if ($task->status === 'blocked' && $oldStatus !== 'blocked') {
             $task->notifyCreatorAndCoordinators(new \App\Notifications\TaskEventNotification($task, 'blocked'));
        }

        if (auth()->user()->cannot('view', $task)) {
            return redirect()->route('teams.tasks.index', $team)
                ->with('success', __('tasks.updated'))
                ->with('warning', 'Acceso prohibido: La tarea ahora es privada y ha dejado de estar visible para ti.');
        }

        return redirect()->route('teams.tasks.show', [$team, $task])
            ->with('success', __('tasks.updated'));
    }

    /**
     * Remove the task from storage
     */
    public function destroy(Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('dashboard')->with('warning', __('teams.unauthorized_access'));
        }
        if (auth()->user()->cannot('delete', $task)) {
            return redirect()->route('teams.tasks.show', [$team, $task])->with('warning', 'No tienes permisos para eliminar esta tarea.');
        }

        // Delete from Google Tasks if synced
        if ($task->google_task_id && auth()->user()->google_token) {
            $googleService = app(\App\Services\GoogleService::class);
            $googleService->deleteTask($task->google_task_list_id, $task->google_task_id);
        }


        $task->delete();

        return redirect()->route('teams.tasks.index', $team)
            ->with('success', __('tasks.deleted'));
    }

    /**
     * Search tasks for autocomplete within the same team context.
     */
    public function search(Request $request, Team $team)
    {
        $queryTerm = $request->input('query');
        $excludeId = $request->input('exclude_id');

        if (auth()->user()->cannot('view', $team)) {
            return response()->json([]);
        }

        $tasks = $team->tasks()
            ->where('title', 'like', '%' . $queryTerm . '%')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->visibleTo(auth()->user(), $team->isManager(auth()->user()))
            ->orderBy('updated_at', 'desc')
            ->limit(25)
            ->get(['id', 'title', 'status']);

        return response()->json($tasks->map(fn($t) => [
            'id' => $t->id,
            'text' => $t->title . ' (' . strtoupper($t->status) . ')',
        ]));
    }

    /**
     * Merge this task into another target task, centralizing all relationships.
     */
    public function merge(Request $request, Team $team, Task $task)
    {
        $request->validate([
            'target_task_id' => 'required|exists:tasks,id',
        ]);

        $targetTask = Task::findOrFail($request->input('target_task_id'));

        if ($targetTask->id === $task->id) {
             return back()->with('warning', 'No puedes fusionar una tarea consigo misma.');
        }

        if ($targetTask->team_id !== $team->id) {
             return back()->with('warning', 'La tarea de destino debe pertenecer al mismo equipo.');
        }

        if (auth()->user()->cannot('delete', $task) || auth()->user()->cannot('update', $targetTask)) {
             return back()->with('warning', 'No tienes permisos suficientes para realizar esta operación.');
        }

        $taskService = app(\App\Services\TaskService::class);
        $taskService->mergeTasks($task, $targetTask);

        return redirect()->route('teams.tasks.show', [$team, $targetTask])
            ->with('success', 'La tarea ha sido fusionada y sus datos migrados correctamente.');
    }

    /**
     * Get tasks by status (API endpoint for AJAX)
     */
    public function byStatus(Team $team, string $status)
    {
        $tasks = $team->tasks()
            ->visibleTo(auth()->user(), $team->isManager(auth()->user()))
            ->operationalFor(auth()->user(), $team)
            ->where('status', $status)
            ->with(['assignedTo', 'tags'])
            ->get();

        return response()->json($tasks);
    }

    /**
     * Get tasks by quadrant (Eisenhower Matrix)
     */
    public function byQuadrant(Team $team)
    {
        $quadrants = [];

        foreach ([1, 2, 3, 4] as $q) {
            $quadrants[$q] = $team->tasks()
                ->visibleTo(auth()->user(), $team->isManager(auth()->user()))
                ->operationalFor(auth()->user(), $team)
                ->with(['assignedTo', 'tags'])
                ->when(session('hide_completed_tasks', true), fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
                ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
                ->orderByRaw("FIELD(status, 'pending', 'blocked', 'in_progress', 'completed', 'cancelled') ASC")
                ->orderBy('progress_percentage', 'desc')
                ->get()
                ->filter(function ($task) use ($q) {
                    return $this->getQuadrant($task) === $q;
                })
                ->values();
        }

        return response()->json([
            'quadrants' => $quadrants,
            'hide_completed' => session('hide_completed_tasks', true),
        ]);
    }

    /**
     * Toggle hide completed tasks preference (session-based)
     */
    public function toggleHideCompleted()
    {
        $current = session('hide_completed_tasks', true);
        session(['hide_completed_tasks' => !$current]);

        return response()->json(['success' => true, 'hide_completed' => !$current]);
    }

    public function toggleSubtasksVisibility(Request $request)
    {
        // If 'show' is provided, we use it (absolute set). Otherwise, toggle.
        $current = session('show_all_subtasks', false);
        $show = $request->has('show') ? $request->boolean('show') : !$current;
        
        session(['show_all_subtasks' => $show]);

        return response()->json(['success' => true, 'show' => $show]);
    }




    /**
     * Manual Sync (Scenario B): Push master template changes to all assigned instances.
     */
    public function syncToChildren(Request $request, Team $team, Task $task)
    {
        $this->authorize('update', $task);

        if (!$task->is_template) {
            return redirect()->back()->with('error', 'Only templates can be synced.');
        }

        $task->instances()->update([
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $task->due_date,
            'priority' => $task->priority,
            'urgency' => $task->urgency,
        ]);

        return redirect()->back()->with('success', __('tasks.synced_success'));
    }


    /**
     * Unified task creation from structured data (used by reproduction and JSON import).
     */

}
