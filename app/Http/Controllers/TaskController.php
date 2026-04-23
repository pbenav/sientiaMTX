<?php

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

class TaskController extends Controller
{
    use HandlesEisenhowerMatrix, AwardsGamification, ManagesTaskDeletion;
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

        // Clone the task
        $newTask = $task->replicate(['uuid', 'google_task_id', 'google_calendar_event_id', 'google_synced_at']);
        $newTask->team_id = $targetTeam->id;
        $newTask->created_by_id = $user->id;
        $newTask->assigned_user_id = $user->id; // Assign to self by default in new team
        $newTask->status = 'pending';
        $newTask->progress_percentage = 0;
        $newTask->parent_id = null; // No parent context in new team
        $newTask->google_task_id = null;
        $newTask->google_calendar_event_id = null;
        $newTask->save();

        // Copy history record
        $newTask->histories()->create([
            'user_id' => $user->id,
            'action' => 'cloned',
            'notes' => 'Clonada desde el equipo: ' . $team->name
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Tarea reproducida correctamente en el equipo :team', ['team' => $targetTeam->name]),
            'url' => route('teams.tasks.show', [$targetTeam, $newTask])
        ]);
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

        $taskData = $data['task'];
        $task = $team->tasks()->create([
            'title' => $taskData['title'],
            'description' => $taskData['description'],
            'observations' => $taskData['observations'],
            'priority' => $taskData['priority'],
            'urgency' => $taskData['urgency'],
            'visibility' => $taskData['visibility'],
            'is_template' => $taskData['is_template'],
            'cognitive_load' => $taskData['cognitive_load'],
            'is_backstage' => $taskData['is_backstage'],
            'autoprogram_settings' => $taskData['autoprogram_settings'],
            'is_out_of_skill_tree' => $taskData['is_out_of_skill_tree'],
            'created_by_id' => auth()->id(),
        ]);

        if (!empty($taskData['skills'])) {
            $skillIds = \App\Models\Skill::whereIn('name', array_column($taskData['skills'], 'name'))->pluck('id');
            $task->skills()->sync($skillIds);
        }

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
                'children' => function($q) use ($user, $isManager) {
                    $q->visibleTo($user, $isManager);
                }
            ]);

        // --- Filters ---
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_user_id', $request->assigned_to);
        }

        if ($request->filled('skill_id')) {
            $skillId = $request->skill_id;
            $query->where(function ($q) use ($skillId) {
                $q->where('skill_id', $skillId)
                  ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'template') {
                $query->where('is_template', true);
            } elseif ($request->type === 'instance') {
                $query->where('is_template', false)->whereNotNull('parent_id');
            } elseif ($request->type === 'plain') {
                $query->where('is_template', false)->whereNull('parent_id');
            }
        }

        // Note: Hierarchy (filtering children/instances) is now handled by scopeOperationalFor
        if ($request->filled('search')) {
            $searchTerm = $request->search;
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
        if (session('hide_completed_tasks', true) && !$request->status) {
            $query->whereNotIn('status', ['completed', 'cancelled']);
        }

        // --- Pagination ---
        $perPage = $request->get('per_page', 10);
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

        return view('tasks.index', compact('team', 'tasks', 'members', 'hideCompleted', 'skills', 'services'));
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

        return view('tasks.create', compact('team', 'users', 'allMembers', 'groups', 'priorities', 'tasks', 'backUrl', 'skills', 'services'));
    }

    /**
     * Store a newly created task in storage
     */
    public function store(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'urgency' => 'required|in:low,medium,high,critical',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|array',
            'assigned_groups' => 'nullable|array',
            'observations' => 'nullable|string',
            'parent_id' => 'nullable|exists:tasks,id',
            'visibility' => 'required|in:public,private',
            'is_autoprogrammable' => 'nullable|boolean',
            'autoprogram_settings' => 'nullable|array',
            'matrix_order' => 'nullable|integer|min:0',
            'skills' => 'nullable|array',
            'skills.*' => 'integer|exists:skills,id',
            'skill_id' => 'nullable|integer|exists:skills,id', // Legacy
            'service_id' => 'nullable|integer|exists:services,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . ((int)ini_get('upload_max_filesize') * 1024),
            'assignment_mode' => 'nullable|string|in:shared,distributed',
        ]);

        $hasAssignments = !empty($validated['assigned_to']) || !empty($validated['assigned_groups']);
        $assignmentMode = $request->input('assignment_mode', 'shared');
        $isTemplate = $hasAssignments && $assignmentMode === 'distributed';

        // AUTO-PUBLIC LOGIC: If private but assigned to others, make it public.
        $autoPublic = false;
        if (($validated['visibility'] ?? 'private') === 'private') {
            $hasOtherAssignee = false;
            if ($request->filled('assigned_user_id') && (int)$request->assigned_user_id !== auth()->id()) {
                $hasOtherAssignee = true;
            }
            if ($request->filled('assigned_to') && collect($request->assigned_to)->reject(fn($id) => (int)$id === auth()->id())->isNotEmpty()) {
                $hasOtherAssignee = true;
            }
            if ($request->filled('assigned_groups')) {
                $hasOtherAssignee = true;
            }
            if ($hasOtherAssignee) {
                $validated['visibility'] = 'public';
                $autoPublic = true;
            }
        }

        $task = $team->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'urgency' => $validated['urgency'],
            'status' => 'pending',
            'scheduled_date' => $validated['scheduled_date'],
            'due_date' => $validated['due_date'],
            'original_due_date' => $validated['due_date'],
            'created_by_id' => auth()->id(),
            'observations' => $validated['observations'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_template' => $isTemplate,
            'visibility' => $validated['visibility'],
            'is_autoprogrammable' => $request->boolean('is_autoprogrammable'),
            'autoprogram_settings' => $request->input('autoprogram_settings'),
            'is_out_of_skill_tree' => $request->boolean('is_out_of_skill_tree'),
            'cognitive_load' => $request->input('cognitive_load', 1),
            'is_backstage' => $request->boolean('is_backstage'),
            'service_id' => $validated['service_id'] ?? null,
        ]);

        if ($task->is_autoprogrammable) {
            // JIT Generation will be handled by the artisan command
            // We just ensure early metadata if needed
            $settings = $task->autoprogram_settings;
            $settings['next_occurrence_at'] = ($task->scheduled_date ? $task->scheduled_date->toDateTimeString() : now()->toDateTimeString());
            $task->update(['autoprogram_settings' => $settings]);
        }

        \Log::info("Task Team ID: " . $task->team_id . " | Team is null: " . ($task->team === null ? "yes" : "no"));
        $task->syncKanbanColumn();

        // Sync Skills
        $skillIds = $request->skills ?? ($request->skill_id ? [$request->skill_id] : []);
        if (!empty($skillIds)) {
            $task->skills()->sync($skillIds);
        }

        // Handle Local Attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $originalName = $file->getClientOriginalName();
                $datePrefix = date('Y-m-d-');
                $fileName = str_starts_with($originalName, $datePrefix) ? $originalName : $datePrefix . $originalName;

                $attachment = $task->attachments()->create([
                    'user_id' => auth()->id(),
                    'file_path' => $path,
                    'file_name' => $fileName,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                AttachmentLog::create([
                    'attachment_id' => $attachment->id,
                    'user_id' => auth()->id(),
                    'action' => 'upload',
                    'metadata' => [
                        'original_name' => $originalName,
                        'size' => $file->getSize()
                    ],
                    'ip_address' => request()->ip()
                ]);
            }
        }

        // Handle Drive Attachments
        if ($request->has('drive_attachments')) {
            $driveFiles = json_decode($request->drive_attachments, true);
            if (is_array($driveFiles)) {
                foreach ($driveFiles as $file) {
                    $attachment = $task->attachments()->create([
                        'user_id' => auth()->id(),
                        'file_name' => $file['name'],
                        'file_path' => 'google_drive/' . $file['id'],
                        'file_size' => $file['size'] ?? 0,
                        'mime_type' => $file['mimeType'] ?? 'application/octet-stream',
                        'storage_provider' => 'google',
                        'provider_file_id' => $file['id'],
                        'web_view_link' => $file['webViewLink'],
                    ]);

                    AttachmentLog::create([
                        'attachment_id' => $attachment->id,
                        'user_id' => auth()->id(),
                        'action' => 'drive_migration', // Linked from drive
                        'metadata' => [
                            'file_id' => $file['id'],
                            'source' => 'google_drive'
                        ],
                        'ip_address' => request()->ip()
                    ]);
                }
            }
        }

        // Create Role-Based Assignments
        $userIds = collect($validated['assigned_to'] ?? []);
        
        if (!empty($validated['assigned_groups'])) {
            foreach ($validated['assigned_groups'] as $groupId) {
                $group = $team->groups()->find($groupId);
                if ($group) {
                    $userIds = $userIds->merge($group->users->pluck('id'));
                }
                $task->assignments()->create([
                    'group_id' => $groupId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }

        // If it's a template and no one is assigned, assign creator
        if ($isTemplate && empty($userIds)) {
            $userIds->push($task->created_by_id);
        }

        // Add creator if they explicitly selected themselves in shared/distributed mode
        if (in_array($task->created_by_id, $validated['assigned_to'] ?? [])) {
            $userIds->push($task->created_by_id);
        }

        $uniqueUserIds = $userIds->unique();

        foreach ($uniqueUserIds as $userId) {
            // Create Assignment record for direct users
            if (in_array($userId, $validated['assigned_to'] ?? [])) {
                $task->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }

            // Create Individual Instance only if it's a Plan Maestro (distributed)
            if ($isTemplate) {
                $instance = $team->tasks()->create([
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'urgency' => $task->urgency,
                    'status' => 'pending',
                    'scheduled_date' => $task->scheduled_date,
                    'due_date' => $task->due_date,
                    'original_due_date' => $task->due_date,
                    'created_by_id' => $task->created_by_id,
                    'observations' => null,
                    'parent_id' => $task->id,
                    'is_template' => false,
                    'assigned_user_id' => $userId,
                    'visibility' => 'private',
                    'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
                    'service_id' => $task->service_id,
                    'cognitive_load' => $task->cognitive_load,
                    'is_backstage' => $task->is_backstage,
                    'skill_id' => $task->skill_id,
                ]);

                if (!empty($skillIds)) {
                    $instance->skills()->sync($skillIds);
                }

                // Notify about Instance
                if ($userId !== auth()->id()) {
                    try {
                        \App\Models\User::find($userId)?->notify(new \App\Notifications\TaskAssignedNotification($instance, auth()->user()));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send TaskAssignedNotification (instance): " . $e->getMessage());
                    }
                }
            } else {
                // Shared Mode: Notify about the main task
                if ($userId !== auth()->id()) {
                    try {
                        \App\Models\User::find($userId)?->notify(new \App\Notifications\TaskAssignedNotification($task, auth()->user()));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send TaskAssignedNotification (shared): " . $e->getMessage());
                    }
                }
            }
        }

        \Log::info("Task Team ID: " . $task->team_id . " | Team is null: " . ($task->team === null ? "yes" : "no"));
        $task->syncKanbanColumn();

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
        return view('tasks.edit', compact('team', 'task', 'users', 'allMembers', 'groups', 'priorities', 'statuses', 'tasks', 'backUrl', 'skills', 'services'));
    }

    /**
     * Update the task in storage
     */
    public function update(Request $request, Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) {
            abort(404);
        }

        if (auth()->user()->cannot('view', $team) || auth()->user()->cannot('update', $task)) {
            return response()->json(['success' => false, 'message' => __('No tienes permisos para modificar esta tarea.')], 403);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'urgency' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:pending,in_progress,completed,cancelled,blocked',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|array',
            'assigned_groups' => 'nullable|array',
            'observations' => 'nullable|string',
            'parent_id' => 'nullable|exists:tasks,id',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'created_by_id' => 'nullable|exists:users,id',
            'visibility' => 'required|in:public,private',
            'is_autoprogrammable' => 'nullable|boolean',
            'autoprogram_settings' => 'nullable|array',
            'skill_id' => 'nullable|integer|exists:skills,id',
            'service_id' => 'nullable|integer|exists:services,id',
            'assignment_mode' => 'nullable|string|in:shared,distributed',
        ]);

        // Store old values for history
        $oldValues = $task->getAttributes();
        $statusChanged = $task->status !== ($validated['status'] ?? $task->status);

        // AUTO-PUBLIC LOGIC: If private but assigned to others, make it public.
        $autoPublic = false;
        $visibility = $validated['visibility'] ?? $task->visibility;
        if ($visibility === 'private') {
            $hasOtherAssignee = false;
            // Check direct assignee in request OR current task if not in request
            $targetAssignee = $request->has('assigned_user_id') ? $request->assigned_user_id : $task->assigned_user_id;
            if ($targetAssignee && (int)$targetAssignee !== auth()->id()) {
                $hasOtherAssignee = true;
            }
            
            // Check collaborators (assigned_to array)
            if ($request->filled('assigned_to') && collect($request->assigned_to)->reject(fn($id) => (int)$id === auth()->id())->isNotEmpty()) {
                $hasOtherAssignee = true;
            }
            
            // Check groups
            if ($request->filled('assigned_groups') && count($request->assigned_groups) > 0) {
                $hasOtherAssignee = true;
            }

            if ($hasOtherAssignee) {
                $visibility = 'public';
                $autoPublic = true;
            }
            $validated['visibility'] = $visibility;
        }

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
            'is_autoprogrammable' => $request->boolean('is_autoprogrammable'),
            'autoprogram_settings' => $request->input('autoprogram_settings'),
            'is_out_of_skill_tree' => $request->boolean('is_out_of_skill_tree'),
            'cognitive_load' => $request->input('cognitive_load', 1),
            'is_backstage' => $request->boolean('is_backstage'),
            'skill_id' => array_key_exists('skill_id', $validated) ? $validated['skill_id'] : $task->skill_id,
            'service_id' => array_key_exists('service_id', $validated) ? $validated['service_id'] : $task->service_id,
        ]);

        if ($task->is_autoprogrammable && (!isset($task->autoprogram_settings['next_occurrence_at']) || $request->has('scheduled_date'))) {
            $settings = $task->autoprogram_settings;
            $settings['next_occurrence_at'] = ($task->scheduled_date ? $task->scheduled_date->toDateTimeString() : now()->toDateTimeString());
            $task->update(['autoprogram_settings' => $settings]);
        }

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
            $task->assignments()->delete();
            
            $assignedTo = array_filter((array) $request->input('assigned_to', []), fn($v) => !is_null($v) && $v !== '');
            $assignedGroups = array_filter((array) $request->input('assigned_groups', []), fn($v) => !is_null($v) && $v !== '');

            // Track previously assigned users to avoid double notifications in shared mode
            $previousUserIds = $task->assignedTo()->pluck('users.id')->toArray();

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
                $task->instances()->whereNotNull('assigned_user_id')->whereNotIn('assigned_user_id', $uniqueUserIds)->delete();

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
                            'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
                            'cognitive_load'     => $task->cognitive_load,
                            'is_backstage'       => $task->is_backstage,
                            'skill_id'           => $task->skill_id,
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
                $task->instances()->whereNotNull('assigned_user_id')->delete();
                $task->assigned_user_id = null; // Mark as unassigned
            }
            $task->save();
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
    public function toggleHideCompleted(Request $request)
    {
        $current = session('hide_completed_tasks', true);
        session(['hide_completed_tasks' => !$current]);
        return response()->json(['hide_completed' => !$current]);
    }

    /**
     * Move task to a different quadrant (Ajax)
     */
    public function move(\Illuminate\Http\Request $request, Team $team, Task $task)
    {
        $this->authorize('update', $task);

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
        if ($request->has('progress_percentage')) $task->progress_percentage = $validated['progress_percentage'];
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
            'parent_progress' => $task->parent_id ? $task->parent->progress_percentage : null
        ]);
    }

    /**
     * Nudge a user assigned to a task instance
     */
    public function nudge(Team $team, Task $task)
    {
        $this->authorize('update', $team);

        if (!$task->isInstance()) {
            return response()->json(['success' => false, 'message' => 'Solo se pueden enviar recordatorios sobre instancias individuales.'], 400);
        }

        $parent = $task->parent;
        $progress = $parent->progress;
        $type = 'collaborative';

        if ($task->status === 'blocked') {
            $type = 'unblocking';
        } elseif ($task->due_date && $task->due_date->isFuture() && $task->due_date->diffInHours(now()) < 24) {
            $type = 'deadline';
        }

        $recipient = $task->assignedUser ?: $task->creator;

        if (!$recipient) {
            return response()->json([
                'success' => false, 
                'message' => 'No hay ningún usuario asociado a esta tarea para notificar.'
            ], 400);
        }

        $recipient->notify(new \App\Notifications\TaskNudgeNotification($task, $type, $progress));

        $task->increment('nudge_count');
        $task->refresh();

        return response()->json([
            'success' => true, 
            'message' => __('tasks.nudge_sent'),
            'nudge_count' => $task->nudge_count
        ]);
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
        $task = $attachment->task;
        if (!$task || $task->team_id !== $team->id) {
            abort(404, 'El archivo solicitado no pertenece a este equipo o no existe.');
        }

        $isManager = $team->isManager(auth()->user());
        
        $hasAccess = Task::where('id', $task->id)->visibleTo(auth()->user(), $isManager)->exists();

        if (!$hasAccess && $task->children()->where('assigned_user_id', auth()->id())->exists()) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            abort(403, 'No tienes permiso para acceder a este archivo.');
        }
    }

    public function updateAttachment(Request $request, Team $team, TaskAttachment $attachment)
    {
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
        // Authorization: Only owner or team manager can delete
        if (auth()->id() !== $attachment->user_id && !$team->isManager(auth()->user())) {
            abort(403);
        }

        // Log deletion BEFORE deleting the attachment record (due to cascade)
        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => auth()->id(),
            'action' => 'delete',
            'metadata' => [
                'file_name' => $attachment->file_name
            ],
            'ip_address' => request()->ip()
        ]);

        // Remove file from disk if exists
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
            
            // Update user disk usage (decrement) only if file existed and was deleted
            $attachment->user->decrement('disk_used', $attachment->file_size);
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
}
