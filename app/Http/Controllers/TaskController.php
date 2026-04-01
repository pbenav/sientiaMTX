<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Models\TaskAttachment;
use App\Traits\HandlesEisenhowerMatrix;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use HandlesEisenhowerMatrix;
    public function index(Request $request, Team $team)
    {
        $user = auth()->user();
        $isManager = $team->isManager($user);
        
        $query = $team->tasks()
            ->visibleTo($user, $isManager)
            ->operationalFor($user, $team)
            ->with(['assignedUser', 'tags', 'creator', 'parent', 'children']);

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
            $query->where('title', 'like', '%' . $searchTerm . '%');

            // Also filter the children relationship so only matched subtasks are shown in the nested view
            $query->with(['children' => function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%');
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

        $tasks = $query->paginate(20)->withQueryString();
        $members = $team->members;
        $hideCompleted = session('hide_completed_tasks', true);

        return view('tasks.index', compact('team', 'tasks', 'members', 'hideCompleted'));
    }

    /**
     * Show the form for creating a new task
     */
    public function create(Team $team)
    {
        $allMembers = $team->members; // All members — for owner selector
        // Exclude the current user from assignee list: creator is implicit owner
        $users = $team->members->reject(fn ($u) => $u->id === auth()->id());
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $tasks = $team->tasks()->orderBy('title')->get();

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            if (!str_contains($referer, "/tasks/create")) {
                session()->put("back_url_task_create_{$team->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_create_{$team->id}", route('teams.dashboard', $team));

        return view('tasks.create', compact('team', 'users', 'allMembers', 'groups', 'priorities', 'tasks', 'backUrl'));
    }

    /**
     * Store a newly created task in storage
     */
    public function store(Request $request, Team $team)
    {
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
        ]);

        $isTemplate = !empty($validated['assigned_to']) || !empty($validated['assigned_groups']);

        // AUTO-PUBLIC LOGIC: If private but assigned to others, make it public.
        $autoPublic = false;
        if (($validated['visibility'] ?? 'private') === 'private') {
            $hasOtherAssignee = false;
            if ($request->filled('assigned_user_id') && (int)$request->assigned_user_id !== auth()->id()) {
                $hasOtherAssignee = true;
            }
            if ($request->filled('assigned_to')) {
                if (collect($request->assigned_to)->reject(fn($id) => (int)$id === auth()->id())->isNotEmpty()) {
                    $hasOtherAssignee = true;
                }
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
        ]);

        if ($task->is_autoprogrammable) {
            // JIT Generation will be handled by the artisan command
            // We just ensure early metadata if needed
            $settings = $task->autoprogram_settings;
            $settings['next_occurrence_at'] = ($task->scheduled_date ? $task->scheduled_date->toDateTimeString() : now()->toDateTimeString());
            $task->update(['autoprogram_settings' => $settings]);
        }

        if ($isTemplate) {
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

            // Always include the creator in the work distribution for template tasks
            $userIds->push($task->created_by_id);

            $uniqueUserIds = $userIds->unique();

            foreach ($uniqueUserIds as $userId) {
                // Create Assignment record (for history/legacy compatibility)
                if (in_array($userId, $validated['assigned_to'] ?? [])) {
                    $task->assignments()->create([
                        'user_id' => $userId,
                        'assigned_by_id' => auth()->id(),
                    ]);
                }

                // Create Individual Instance
                $team->tasks()->create([
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'urgency' => $task->urgency,
                    'status' => 'pending',
                    'scheduled_date' => $task->scheduled_date,
                    'due_date' => $task->due_date,
                    'original_due_date' => $task->due_date,
                    'created_by_id' => $task->created_by_id,
                    'observations' => null, // Reset observations for private task
                    'parent_id' => $task->id,
                    'is_template' => false,
                    'assigned_user_id' => $userId,
                    'visibility' => 'private',
                ]);
            }
        }

        $task->syncKanbanColumn();

        return redirect()->route('teams.tasks.show', [$team, $task])
            ->with($autoPublic ? 'warning' : 'success', $autoPublic ? __('tasks.auto_public_warning') : __('tasks.created'));
    }

    /**
     * Display the specified task
     */
    public function show(Team $team, Task $task)
    {
        $this->authorize('view', $task);
        // Proactively ensure the creator has a personal instance if this is a template task
        if ($task->is_template && !$task->instances()->where('assigned_user_id', $task->created_by_id)->exists()) {
            $team->tasks()->create([
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'urgency' => $task->urgency,
                'status' => 'pending',
                'scheduled_date' => $task->scheduled_date,
                'due_date' => $task->due_date,
                'original_due_date' => $task->due_date,
                'created_by_id' => $task->created_by_id,
                'parent_id' => $task->id,
                'is_template' => false,
                'assigned_user_id' => $task->created_by_id,
                'visibility' => 'private',
            ]);
        }

        $task->load(['assignedTo', 'assignedGroups', 'creator', 'histories', 'tags', 'attachments']);

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
        if (auth()->user()->cannot('update', $task)) {
            return redirect()->route('teams.tasks.show', [$team, $task])
                ->with('warning', __('No tienes permisos para modificar esta tarea privada.'));
        }
        $allMembers = $team->members; // All members — for owner selector
        // Exclude the current user from assignee list: creator is implicit owner
        $users = $team->members->reject(fn ($u) => $u->id === auth()->id());
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $statuses = ['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada', 'blocked' => 'Bloqueada'];
        $tasks = $team->tasks()->where('id', '!=', $task->id)->orderBy('title')->get();

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            if (!str_contains($referer, "/tasks/{$task->id}/edit")) {
                session()->put("back_url_task_edit_{$task->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_edit_{$task->id}", route('teams.tasks.show', [$team, $task]));

        return view('tasks.edit', compact('team', 'task', 'users', 'allMembers', 'groups', 'priorities', 'statuses', 'tasks', 'backUrl'));
    }

    /**
     * Update the task in storage
     */
    public function update(Request $request, Team $team, Task $task)
    {
        $this->authorize('update', $task);
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
        ]);

        // Store old values for history
        $oldValues = $task->getAttributes();
        $statusChanged = $task->status !== $validated['status'];

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
            if ($request->has('assigned_to')) {
                if (collect($request->assigned_to)->reject(fn($id) => (int)$id === auth()->id())->isNotEmpty()) {
                    $hasOtherAssignee = true;
                }
            } elseif ($task->assignedTo()->where('users.id', '!=', auth()->id())->exists()) {
                // If not providing assigned_to in request, check existing ones
                $hasOtherAssignee = true;
            }

            if ($hasOtherAssignee) {
                $visibility = 'public';
                $autoPublic = true;
            }
            $validated['visibility'] = $visibility;
        }

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'urgency' => $validated['urgency'],
            'status' => $validated['status'],
            'scheduled_date' => $validated['scheduled_date'],
            'due_date' => $validated['due_date'],
            'observations' => $validated['observations'],
            'parent_id' => $validated['parent_id'] ?? null,
            'progress_percentage' => $validated['progress_percentage'] ?? $task->progress_percentage,
            'visibility' => $validated['visibility'],
            'is_autoprogrammable' => $request->boolean('is_autoprogrammable'),
            'autoprogram_settings' => $request->input('autoprogram_settings'),
        ]);

        if ($team->isCoordinator(auth()->user()) && isset($validated['created_by_id'])) {
            $task->created_by_id = $validated['created_by_id'];
            $task->save();
        }

        // Sync status based on progress
        if ($task->progress_percentage == 100 && $task->status !== 'completed' && $task->status !== 'cancelled') {
            $task->status = 'completed';
            $task->save();
        } elseif ($task->progress_percentage < 100 && $task->status === 'completed') {
            $task->status = 'in_progress';
            $task->save();
        }

        // If this is a template, sync core fields to instances
        if ($task->is_template) {
            $task->instances()->update([
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
                'urgency' => $task->urgency,
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
            
            $assignedTo = $request->input('assigned_to', []);
            $assignedGroups = $request->input('assigned_groups', []);

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

            // Determine if it should still be a template
            $isTemplate = !empty($assignedTo) || !empty($assignedGroups);
            $task->is_template = $isTemplate;
            $task->save();
            
            if ($isTemplate) {
                // Calculate unique users
                $userIds = collect($assignedTo);
                foreach ($assignedGroups as $groupId) {
                    $group = $team->groups()->find($groupId);
                    if ($group) {
                        $userIds = $userIds->merge($group->users->pluck('id'));
                    }
                }

                // Always include the creator in the work distribution for template tasks
                $userIds->push($task->created_by_id);

                $uniqueUserIds = $userIds->unique();

                // Sync instances
                $task->instances()->whereNotIn('assigned_user_id', $uniqueUserIds)->delete();
                foreach ($uniqueUserIds as $userId) {
                    if (!$task->instances()->where('assigned_user_id', $userId)->exists()) {
                        $team->tasks()->create([
                            'title' => $task->title,
                            'description' => $task->description,
                            'priority' => $task->priority,
                            'urgency' => $task->urgency,
                            'status' => 'pending',
                            'scheduled_date' => $task->scheduled_date,
                            'due_date' => $task->due_date,
                            'original_due_date' => $task->due_date,
                            'created_by_id' => $task->created_by_id, // Owner stays owner
                            'observations' => null,
                            'parent_id' => $task->id,
                            'is_template' => false,
                            'assigned_user_id' => $userId,
                            'visibility' => 'private',
                        ]);
                    }
                }
            } else {
                // Not a template anymore: clean up
                $task->instances()->delete();
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
        $this->authorize('delete', $task);

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
     * Remove multiple tasks from storage
     */
    public function bulkDelete(\Illuminate\Http\Request $request, Team $team)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id'
        ]);

        $tasks = Task::whereIn('id', $request->task_ids)->where('team_id', $team->id)->get();
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
                ->when(session('hide_completed_tasks', true) && !$request->status, fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
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
            if (!$request->has('status')) $task->status = 'in_progress';
        }

        // Final save for the main task
        $task->save();

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

        if ($task->isInstance() && $request->has('status') && $task->status === 'completed') {
            $parent = $task->parent;
            $totalChildren = $parent->children()->count();
            $completedChildren = $parent->children()->where('status', 'completed')->count();
            $progress = $totalChildren > 0 ? ($completedChildren / $totalChildren) * 100 : 0;

            if ($completedChildren === $totalChildren && $parent->status !== 'completed') {
                $parent->update(['status' => 'completed', 'progress_percentage' => 100]);
            }

            if (in_array((int)$progress, [50, 75, 100])) {
                $team->creator->notify(new \App\Notifications\TaskMilestoneNotification($parent, (int)$progress));
            }
        }

        if ($task->parent_id && $request->has('progress_percentage')) {
            $parent = $task->parent;
            $parent->update(['progress_percentage' => $parent->progress]);
        }

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

        $task->assignedUser->notify(new \App\Notifications\TaskNudgeNotification($task, $type, $progress));

        return response()->json([
            'success' => true,
            'message' => __('tasks.nudge_sent')
        ]);
    }

    public function uploadAttachment(\Illuminate\Http\Request $request, Team $team, Task $task)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max per file
        ]);

        $user = auth()->user();
        $file = $request->file('file');
        $size = $file->getSize();

        // Check user quota
        if (!$user->hasAvailableQuota($size)) {
            return back()->with('error', 'Has excedido tu cuota de espacio en disco.');
        }

        $path = $file->store("attachments/task_{$task->id}", 'public');

        $attachment = $task->attachments()->create([
            'user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $size,
            'mime_type' => $file->getMimeType(),
        ]);

        // Update user disk usage
        $user->increment('disk_used', $size);

        return back()->with('success', 'Archivo adjuntado correctamente.');
    }

    public function downloadAttachment(Team $team, TaskAttachment $attachment)
    {
        $task = $attachment->task;
        $isManager = $team->isManager(auth()->user());
        
        $hasAccess = Task::where('id', $task->id)->visibleTo(auth()->user(), $isManager)->exists();

        if (!$hasAccess && $task->children()->where('assigned_user_id', auth()->id())->exists()) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            abort(403, 'No tienes permiso para descargar este archivo.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'El archivo no se encuentra en el servidor.');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function updateAttachment(Request $request, Team $team, TaskAttachment $attachment)
    {
        $validated = $request->validate([
            'file_name' => 'required|string|max:255',
        ]);

        $attachment->update([
            'file_name' => $validated['file_name'],
        ]);

        return back()->with('success', 'Archivo renombrado correctamente.');
    }

    public function destroyAttachment(Team $team, TaskAttachment $attachment)
    {
        // Remove file from disk if exists
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
            
            // Update user disk usage (decrement) only if file existed and was deleted
            $attachment->user->decrement('disk_used', $attachment->file_size);
        }
        
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }
}
