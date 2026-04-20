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
    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
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

        // --- Pagination ---
        $perPage = $request->get('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100, 'all'])) {
            $perPage = 25;
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

        return view('tasks.index', compact('team', 'tasks', 'members', 'hideCompleted', 'skills'));
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

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            if (!str_contains($referer, "/tasks/create")) {
                session()->put("back_url_task_create_{$team->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_create_{$team->id}", route('teams.dashboard', $team));

        return view('tasks.create', compact('team', 'users', 'allMembers', 'groups', 'priorities', 'tasks', 'backUrl', 'skills'));
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
            'is_out_of_skill_tree' => $request->boolean('is_out_of_skill_tree'),
            'cognitive_load' => $request->input('cognitive_load', 1),
            'is_backstage' => $request->boolean('is_backstage'),
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

        // Handle Attachments directly from the create form
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
                    'cognitive_load' => $task->cognitive_load,
                    'is_backstage' => $task->is_backstage,
                    'skill_id' => $task->skill_id,
                ]);

                if (!empty($skillIds)) {
                    $instance->skills()->sync($skillIds);
                }

                // Notify about Instance
                if ($userId !== auth()->id()) {
                    \App\Models\User::find($userId)?->notify(new TaskAssignedNotification($instance, auth()->user()));
                }
            } else {
                // Shared Mode: Notify about the main task
                if ($userId !== auth()->id()) {
                    \App\Models\User::find($userId)?->notify(new TaskAssignedNotification($task, auth()->user()));
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
            return redirect()->back()->with('warning', __('tasks.unauthorized_view'));
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

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            if (!str_contains($referer, "/tasks/{$task->id}/edit")) {
                session()->put("back_url_task_edit_{$task->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_edit_{$task->id}", route('teams.tasks.show', [$team, $task]));

        return view('tasks.edit', compact('team', 'task', 'users', 'allMembers', 'groups', 'priorities', 'statuses', 'tasks', 'backUrl', 'skills'));
    }

    /**
     * Update the task in storage
     */
    public function update(Request $request, Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) {
            abort(404);
        }

        if (auth()->user()->cannot('view', $team)) {
            return response()->json(['success' => false, 'message' => __('teams.unauthorized_access')], 403);
        }
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
            'skill_id' => 'nullable|integer|exists:skills,id',
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
            'title' => $validated['title'] ?? $task->title,
            'description' => $validated['description'] ?? $task->description,
            'priority' => $validated['priority'] ?? $task->priority,
            'urgency' => $validated['urgency'] ?? $task->urgency,
            'status' => $validated['status'] ?? $task->status,
            'scheduled_date' => $validated['scheduled_date'] ?? $task->scheduled_date,
            'due_date' => $validated['due_date'] ?? $task->due_date,
            'observations' => $validated['observations'] ?? $task->observations,
            'parent_id' => $validated['parent_id'] ?? $task->parent_id,
            'progress_percentage' => $validated['progress_percentage'] ?? $task->progress_percentage,
            'visibility' => $validated['visibility'] ?? $task->visibility,
            'is_autoprogrammable' => $request->boolean('is_autoprogrammable'),
            'autoprogram_settings' => $request->input('autoprogram_settings'),
            'is_out_of_skill_tree' => $request->boolean('is_out_of_skill_tree'),
            'cognitive_load' => $request->input('cognitive_load', 1),
            'is_backstage' => $request->boolean('is_backstage'),
            'skill_id' => $validated['skill_id'] ?? $task->skill_id,
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

        // SCENARIO B: Metadata Independence
        // We no longer automatically sync titles and descriptions across the hierarchy.
        // This allows members to have their own 'aliases' and notes without affecting the project skeleton.
        // Aggregate progress and status are still synced (handled above).
        
        /* 
        // Legacy Sync Logic (Scenario A)
        if ($task->is_template) {
            $task->instances()->update([
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
                'urgency' => $task->urgency,
            ]);
        }
        */

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
                        \App\Models\User::find($userId)?->notify(new TaskAssignedNotification($task, auth()->user()));
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
                            \App\Models\User::find($userId)?->notify(new TaskAssignedNotification($inst, auth()->user()));
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
            if (!$request->has('status')) $task->status = 'in_progress';
        }

        if ($request->has('matrix_order')) {
            $task->matrix_order = $validated['matrix_order'];
        }

        // Final save for the main task
        $task->save();

        // Handle bulk reordering if full_order is provided
        if ($request->has('full_order') && is_array($request->full_order)) {
            foreach ($request->full_order as $index => $id) {
                // Bulk update to minimize DB queries or keep it simple?
                // For safety and triggers, individual update is okay for matrix size
                \App\Models\Task::where('id', $id)
                    ->where('team_id', $team->id)
                    ->update(['matrix_order' => $index]);
            }
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
        if (!$task) {
            abort(404, 'La tarea asociada a este archivo no existe.');
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
        // Actually, we want to keep the logs but the attachment will be gone.
        // Wait, the logs table has a constrained foreign key with onDelete('cascade').
        // If we want to keep logs, we should make attachment_id nullable or remove cascade.
        // The user said "tabla de metainformación... histórico". 
        // If the attachment is deleted, maybe the logs should stay but with a null attachment_id?
        // But the user didn't specify. For now, I'll keep the cascade.
        // But I'll log it.
        
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
}
