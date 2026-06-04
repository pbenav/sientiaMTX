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
        $newTask = \DB::transaction(function () use ($task, $targetTeam, $user) {
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

        $clonedTask = \DB::transaction(function () use ($task, $team, $user) {
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
            \Log::warning('JSON Import Error: ' . json_last_error_msg() . ' / JSON String: ' . $json);
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

        $filename = 'task-' . \Illuminate\Support\Str::slug($task->title) . '-' . date('YmdHis') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
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
            $query->where('status', $filters['status']);
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
            ->with($autoPublic ? 'warning' : 'success', $autoPublic ? __('tasks.auto_public_warning') : __('tasks.created'));
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
            return redirect()->route('teams.dashboard', $team)->with('warning', 'La tarea no está accesible o es privada.');
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
            return redirect()->route('teams.tasks.show', [$team, $task])
                ->with('warning', __('No tienes permisos para modificar esta tarea privada.'));
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

        $task->update([
            'title' => array_key_exists('title', $validated) ? $validated['title'] : $task->title,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $task->description,
            'priority' => array_key_exists('priority', $validated) ? $validated['priority'] : $task->priority,
            'urgency' => array_key_exists('urgency', $validated) ? $validated['urgency'] : $task->urgency,
            'status' => array_key_exists('status', $validated) ? $validated['status'] : $task->status,
            'scheduled_date' => array_key_exists('scheduled_date', $validated) ? $validated['scheduled_date'] : $task->scheduled_date,
            'due_date' => array_key_exists('due_date', $validated) ? $validated['due_date'] : $task->due_date,
            'observations' => array_key_exists('observations', $validated) ? $validated['observations'] : $task->observations,
            'parent_id' => array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $task->parent_id,
            'progress_percentage' => array_key_exists('progress_percentage', $validated) ? $validated['progress_percentage'] : $task->progress_percentage,
            'visibility' => array_key_exists('visibility', $validated) ? $validated['visibility'] : $task->visibility,
            'is_autoprogrammable' => $request->has('is_autoprogrammable') ? $request->boolean('is_autoprogrammable') : $task->is_autoprogrammable,
            'autoprogram_settings' => $request->has('autoprogram_settings') ? $request->input('autoprogram_settings') : $task->autoprogram_settings,
            'is_out_of_skill_tree' => $request->boolean('is_out_of_skill_tree'),
            'cognitive_load' => $request->input('cognitive_load', 1),
            'is_backstage' => $request->boolean('is_backstage'),
            'skill_id' => array_key_exists('skill_id', $validated) ? $validated['skill_id'] : $task->skill_id,
            'service_id' => array_key_exists('service_id', $validated) ? $validated['service_id'] : $task->service_id,
            'expediente_id' => ($task->parent_id && !$task->is_template) 
                ? $task->expediente_id 
                : (array_key_exists('expediente_id', $validated) ? $validated['expediente_id'] : $task->expediente_id),
            'is_timeline_locked' => $request->boolean('is_timeline_locked'),
        ]);


        if ($team->isCoordinator(auth()->user()) && isset($validated['created_by_id'])) {
            $task->created_by_id = $validated['created_by_id'];
            $task->save();
        }

        // Sync Skills
        $skillIds = $request->skills ?? ($request->skill_id ? [$request->skill_id] : []);
        $task->skills()->sync($skillIds);

        // Propagate skills to instances if this is a template
        if ($task->is_template) {
            foreach($task->instances as $inst) {
                $inst->skills()->sync($skillIds);
            }
        }

        // Sync status based on progress
        if ($task->progress_percentage == 100 && $task->status !== 'completed' && $task->status !== 'cancelled') {
            $task->status = 'completed';
            $task->save();
        } elseif ($task->progress_percentage < 100 && $task->status === 'completed') {
            $task->status = 'in_progress';
            $task->save();
        }

        // Gamification: Award points if completed
        if ($task->status === 'completed' && $oldValues['status'] !== 'completed') {
            $this->awardGamificationPoints($task);
            $task->notifyCoordinatorsIfCompleted();
        }

        // Notification for Blocked status
        if ($task->status === 'blocked' && $oldValues['status'] !== 'blocked') {
             $task->notifyCreatorAndCoordinators(new TaskEventNotification($task, 'blocked'));
        }

        // Notification for Milestones
        $oldProgress = (int) ($oldValues['progress_percentage'] ?? 0);
        $newProgress = (int) ($task->progress_percentage ?? 0);

        if ($newProgress >= 50 && $oldProgress < 50) {
             $task->notifyCreatorAndCoordinators(new TaskEventNotification($task, 'milestone_50'));
        }
        if ($newProgress >= 75 && $oldProgress < 75) {
             $task->notifyCreatorAndCoordinators(new TaskEventNotification($task, 'milestone_75'));
        }

        // Parent progress sync
        if ($task->parent_id) {
            $currentParent = $task->parent;
            while ($currentParent) {
                $currentParent->update(['progress_percentage' => $currentParent->progress]);
                $currentParent = $currentParent->parent;
            }
        }

        // SCENARIO B: Metadata Independence (Partial Sync)
        // We preserve title and description independence, but sync core project skeleton attributes
        // to ensure team alignment on deadlines and importance.
        if ($task->is_template) {
            $task->instances()->update([
                'priority' => $task->priority,
                'urgency' => $task->urgency,
                'due_date' => $task->due_date,
                'original_due_date' => $task->due_date,
                'expediente_id' => $task->expediente_id, // Synchronize expediente too
            ]);
        }

        // Log changes to history
        $newValues = $task->getAttributes();
        $changes = array_diff_assoc($newValues, $oldValues);

        if (!empty($changes)) {
            $task->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        }

        // Only allow coordinators, managers or the owner to change assignments, visibility or core metadata
        $isCoordinator = $team->isCoordinator(auth()->user()) || auth()->id() === $task->created_by_id;

        if ($request->has('title') && $isCoordinator) {
            // Track previously assigned users to avoid double notifications in shared mode
            $previousUserIds = $task->assignedTo()->pluck('users.id')->toArray();

            $assignedTo = array_filter((array) $request->input('assigned_to', []), fn($v) => !is_null($v) && $v !== '');
            $assignedGroups = array_filter((array) $request->input('assigned_groups', []), fn($v) => !is_null($v) && $v !== '');

            $task->assignments()->delete();

            foreach ($assignedTo as $userId) {
                $task->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }

            foreach ($assignedGroups as $groupId) {
                $task->assignments()->create([
                    'group_id' => $groupId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }

            // Determine unique assigned users for notification logic
            $userIds = collect($assignedTo);
            foreach ($assignedGroups as $groupId) {
                $group = $team->groups()->find($groupId);
                if ($group) {
                    $userIds = $userIds->merge($group->users->pluck('id'));
                }
            }
            $uniqueUserIds = $userIds->unique();

            // Determine if it should still be a template
            $hasAssignments = !empty($assignedTo) || !empty($assignedGroups);
            $assignmentMode = $request->input('assignment_mode', 'shared');
            $isTemplate = $hasAssignments && $assignmentMode === 'distributed';
            $task->is_template = $isTemplate;
            $task->save();

            // Notify NEW users in Shared Mode
            if (!$isTemplate) {
                $newUserIds = $uniqueUserIds->diff($previousUserIds);
                foreach ($newUserIds as $userId) {
                    if ((int)$userId !== (int)auth()->id()) {
                        try {
                            \App\Models\User::find($userId)?->notify(new \App\Notifications\TaskAssignedNotification($task, auth()->user()));
                        } catch (\Exception $e) {
                            \Log::error("Failed to send TaskAssignedNotification (update-shared): " . $e->getMessage());
                        }
                    }
                }
            }
            
            if ($isTemplate) {

                // Sync instances
                // Delete instances not belonging to the new user set (including orphaned null-assigned ones)
                // ONLY delete instances that were previously assigned to users who are no longer in the set.
                // We PROTECT "unassigned" subtasks (Project Skeleton / Manual Subtasks) by ignoring whereNull.
                $task->instances()
                    ->whereNotNull('assigned_user_id')
                    ->where('metadata->is_occurrence', '!=', true) // Protect occurrences from distributed sync
                    ->whereNotIn('assigned_user_id', $uniqueUserIds)
                    ->get()
                    ->each
                    ->delete();

                foreach ($uniqueUserIds as $userId) {
                    if (!$task->instances()->where('assigned_user_id', $userId)->exists()) {
                        $inst = $team->tasks()->create([
                            'title'              => $task->title,
                            'description'        => $task->description,
                            'priority'           => $task->priority,
                            'urgency'            => $task->urgency,
                            'status'             => 'pending',
                            'scheduled_date'     => $task->scheduled_date,
                            'due_date'           => $task->due_date,
                            'original_due_date'  => $task->due_date,
                            'created_by_id'      => $task->created_by_id,
                            'observations'       => null,
                            'parent_id'          => $task->id,
                            'is_template'        => false,
                            'assigned_user_id'   => $userId,
                            'expediente_id'      => $task->expediente_id,
                            'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
                            'cognitive_load'     => $task->cognitive_load,
                            'is_backstage'       => $task->is_backstage,
                            'skill_id'           => $task->skill_id,
                            'service_id'         => $task->service_id, // Add service_id as well just in case
                            'visibility'         => 'private',
                        ]);

                        // Notify during update if new instance
                        if ($userId !== auth()->id()) {
                            try {
                                \App\Models\User::find($userId)?->notify(new \App\Notifications\TaskAssignedNotification($inst, auth()->user()));
                            } catch (\Exception $e) {
                                \Log::error("Failed to send TaskAssignedNotification: " . $e->getMessage());
                            }
                        }
                    }
                }
            } else {
                // Not a template anymore: clean up
                // Not a template anymore: clean up only if NOT an occurrence
                $task->instances()
                    ->whereNotNull('assigned_user_id')
                    ->where('metadata->is_occurrence', '!=', true) // Protect occurrences
                    ->get()
                    ->each
                    ->delete();
                $task->assigned_user_id = null; // Mark as unassigned
            }
            $task->save();
        }

        // --- AUTOPROGRAMMING TRIGGER (Now at the end to ensure assignments are ready) ---
        if ($task->is_autoprogrammable) {
            $settings = $task->autoprogram_settings;
            if (!isset($settings['next_occurrence_at']) || $task->wasChanged('scheduled_date')) {
                $settings['next_occurrence_at'] = ($task->scheduled_date ? $task->scheduled_date->toDateTimeString() : now()->toDateTimeString());
                $task->update(['autoprogram_settings' => $settings]);
            }
            $task->autoWakeup();
        }

        $task->syncKanbanColumn();

        return redirect()->route('teams.tasks.show', [$team, $task])
            ->with($autoPublic ? 'warning' : 'success', $autoPublic ? __('tasks.auto_public_warning') : __('tasks.updated'));
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

        \DB::transaction(function () use ($task, $targetTask) {
             // 1. Combine content additively if source brings something new
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

             // 2. Reassign subtasks
             $task->children()->update(['parent_id' => $targetTask->id]);

             // 3. Transfer Time Logs
             $task->timeLogs()->update(['task_id' => $targetTask->id]);

             // 4. Transfer Morphic Attachments
             \App\Models\TaskAttachment::where('attachable_type', Task::class)
                 ->where('attachable_id', $task->id)
                 ->update(['attachable_id' => $targetTask->id]);
            
             \App\Models\TaskAttachment::where('attachable_type', 'App\Models\Task')
                 ->where('attachable_id', $task->id)
                 ->update(['attachable_id' => $targetTask->id]);

             // 5. Transfer Private Notes
             $task->privateNotes()->update(['task_id' => $targetTask->id]);

             // 6. Transfer Kudos
             \App\Models\Kudo::where('task_id', $task->id)->update(['task_id' => $targetTask->id]);

             // 7. Transfer Task History trail
             $task->histories()->update(['task_id' => $targetTask->id]);

             // 8. Merge Tags without duplication
             foreach($task->tags as $tag) {
                 $exists = $targetTask->tags()->where('tag', $tag->tag)->exists();
                 if (!$exists) {
                     $tag->update(['task_id' => $targetTask->id]);
                 }
             }

             // 9. Merge User/Group Assignments ensuring uniqueness
             foreach($task->assignments as $assignment) {
                 $existsQuery = $targetTask->assignments();
                 if ($assignment->user_id) {
                     $existsQuery->where('user_id', $assignment->user_id);
                 } else {
                     $existsQuery->where('group_id', $assignment->group_id);
                 }
                 
                 if (!$existsQuery->exists()) {
                     $assignment->update(['task_id' => $targetTask->id]);
                 }
             }

             // 10. Forum Thread resolution: Transfer messages or adopt orphan thread
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

             // 11. Calendar Event adoption
             $sourceCal = $task->calendarEvent;
             if ($sourceCal) {
                 if (!$targetTask->calendarEvent()->exists()) {
                     $sourceCal->update(['task_id' => $targetTask->id]);
                 } else {
                     $sourceCal->delete();
                 }
             }

             // 12. Finally destroy source task tracking the history for destination
             $targetTask->histories()->create([
                 'user_id' => auth()->id(),
                 'action' => 'task_merged',
                 'notes' => "Tarea ID #{$task->id} ('{$task->title}') ha sido fusionada en esta tarea."
             ]);

             $task->delete();
        });

        return redirect()->route('teams.tasks.show', [$team, $targetTask])
            ->with('success', 'La tarea ha sido fusionada y sus datos migrados correctamente.');
    }

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

        $tasks = Task::whereIn('id', $request->task_ids)
            ->where('team_id', $team->id)
            ->get();

        $updatedCount = 0;
        $field = $request->field;
        $value = $request->value;

        foreach ($tasks as $task) {
            if (auth()->user()->can('update', $task)) {
                $oldValue = $task->{$field};
                
                // Special check for assignment: update visibility if needed
                if ($field === 'assigned_user_id' && (int)$value !== auth()->id() && $task->visibility === 'private') {
                    $task->visibility = 'public';
                }

                $task->update([$field => $value]);
                
                // If status changed to completed, award points
                if ($field === 'status' && $value === 'completed' && $oldValue !== 'completed') {
                    $this->awardGamificationPoints($task);
                    $task->notifyCoordinatorsIfCompleted();
                }

                // If collaborator assigned, notify
                if ($field === 'assigned_user_id' && (int)$value !== auth()->id() && $oldValue != $value) {
                    try {
                        \App\Models\User::find($value)?->notify(new \App\Notifications\TaskAssignedNotification($task, auth()->user()));
                    } catch (\Exception $e) { /* Ignore notification errors */ }
                }

                // Log history
                $task->histories()->create([
                    'user_id' => auth()->id(),
                    'action' => 'bulk_updated',
                    'old_values' => [$field => $oldValue],
                    'new_values' => [$field => $value],
                    'notes' => "Actualización masiva de {$field}"
                ]);

                $updatedCount++;
            }
        }

        return back()->with('success', "Se han actualizado {$updatedCount} tareas correctamente.");
    }

    /**
     * Remove multiple tasks from storage
     */
    public function bulkDelete(\Illuminate\Http\Request $request, Team $team)
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
                        \Illuminate\Support\Facades\Log::error('Bulk delete Google Task error: ' . $e->getMessage());
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

            \DB::transaction(function () use ($task, $targetTask) {
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
     * Move task to a different quadrant (Ajax)
     */
    public function move(\Illuminate\Http\Request $request, Team $team, Task $task)
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
            'task_ids'      => 'required|array',
            'task_ids.*'    => 'exists:tasks,id',
            'custom_message'=> 'nullable|string|max:500'
        ]);

        $tasks   = Task::whereIn('id', $validated['task_ids'])->where('team_id', $team->id)->get();
        $sent    = 0;
        $failed  = 0;
        $skipped = 0;

        foreach ($tasks as $task) {
            $type      = 'collaborative';
            $progress  = $task->progress;

            if ($task->status === 'blocked') {
                $type = 'unblocking';
            } elseif ($task->due_date && $task->due_date->isFuture() && $task->due_date->diffInHours(now()) < 24) {
                $type = 'deadline';
            }

            $recipientId = $request->input('user_id');
            $recipient   = $recipientId
                ? \App\Models\User::find($recipientId)
                : ($task->assignedUser ?: $task->creator);

            if (!$recipient) {
                $skipped++;
                continue;
            }

            try {
                $recipient->notify(new \App\Notifications\TaskNudgeNotification(
                    $task, $type, $progress, $validated['custom_message']
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

    public function uploadAttachment(\Illuminate\Http\Request $request, Team $team, Task $task)
    {
        if (auth()->user()->cannot('view', $team)) {
            return back()->with('error', __('teams.unauthorized_access'));
        }

        if ($task->team_id !== $team->id) {
            abort(404);
        }

        $maxSizeKB = (int)ini_get('upload_max_filesize') * 1024;
        $request->validate([
            'file' => "required|file|max:$maxSizeKB",
        ]);

        $user = auth()->user();
        $file = $request->file('file');
        $size = $file->getSize();

        // Check user quota
        if (!$user->hasAvailableQuota($size)) {
            return back()->with('error', 'Has excedido tu cuota de espacio en disco.');
        }

        // Check TEAM quota
        if (!$team->hasAvailableQuota($size)) {
            return back()->with('error', '⚠️ El equipo ha alcanzado su límite de almacenamiento. Un coordinador debe liberar espacio antes de poder subir más archivos.');
        }

        $path = $file->store("attachments/task_{$task->id}", 'public');

        $originalName = $file->getClientOriginalName();
        $datePrefix = date('Y-m-d-');
        $fileName = str_starts_with($originalName, $datePrefix) ? $originalName : $datePrefix . $originalName;

        $attachment = $task->attachments()->create([
            'user_id' => $user->id,
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => $size,
            'mime_type' => $file->getMimeType(),
        ]);

        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => $user->id,
            'action' => 'upload',
            'metadata' => [
                'original_name' => $originalName,
                'size' => $size
            ],
            'ip_address' => request()->ip()
        ]);

        // Update user disk usage
        $user->increment('disk_used', $size);

        return back()->with('success', 'Archivo adjuntado correctamente.');
    }

    public function downloadAttachment(Team $team, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($team, $attachment);

        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'El archivo no se encuentra en el servidor.');
        }

        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'download',
            'ip_address' => request()->ip()
        ]);

        return \Illuminate\Support\Facades\Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function viewAttachment(Team $team, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($team, $attachment);

        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'El archivo no se encuentra en el servidor.');
        }

        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'view',
            'ip_address' => request()->ip()
        ]);

        return \Illuminate\Support\Facades\Storage::disk('public')->response($attachment->file_path);
    }

    protected function authorizeAttachmentAccess(Team $team, TaskAttachment $attachment)
    {
        if (!$attachment->canBeAccessedBy(auth()->user(), $team)) {
            abort(403, 'No tienes permiso para acceder a este archivo.');
        }
    }

    public function updateAttachment(Request $request, Team $team, TaskAttachment $attachment)
    {
        $this->authorize('update', $attachment);

        $validated = $request->validate([
            'file_name' => 'required|string|max:255',
        ]);

        $oldName = $attachment->file_name;
        $attachment->update([
            'file_name' => $validated['file_name'],
        ]);

        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'rename',
            'metadata' => [
                'old_name' => $oldName,
                'new_name' => $validated['file_name']
            ],
            'ip_address' => request()->ip()
        ]);

        return back()->with('success', 'Archivo renombrado correctamente.');
    }

    public function destroyAttachment(Team $team, TaskAttachment $attachment)
    {
        $this->authorize('delete', $attachment);

        // Log deletion BEFORE deleting the attachment record (due to cascade)
        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'delete',
            'metadata' => [
                'file_name' => $attachment->file_name,
                'storage_provider' => $attachment->storage_provider
            ],
            'ip_address' => request()->ip()
        ]);

        // Remove file from disk if exists
        if ($attachment->storage_provider === 'local' && \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
            
            // Update user disk usage (decrement) only if file existed and was deleted
            $attachment->user->decrement('disk_used', $attachment->file_size);
        } elseif ($attachment->storage_provider === 'google' && $attachment->provider_file_id) {
            // ONLY delete from Google Drive if explicitly requested
            if (request()->boolean('delete_from_drive')) {
                try {
                    $googleService = app(\App\Services\Google\GoogleDriveService::class);
                    $googleService->deleteFile(auth()->user(), $attachment->provider_file_id, $team->id);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to delete from Google Drive during attachment destruction: ' . $e->getMessage());
                }
            }
        }
        
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    /**
     * Get attachment history logs
     */
    public function attachmentHistory(Team $team, TaskAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($team, $attachment);

        $attachment->load('user');
        $logs = AttachmentLog::where('attachment_id', $attachment->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'attachment' => $attachment,
            'logs' => $logs
        ]);
    }

    /**
     * Unified task creation from structured data (used by reproduction and JSON import).
     */
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
}
