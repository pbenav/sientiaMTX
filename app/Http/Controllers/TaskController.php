<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Traits\HandlesEisenhowerMatrix;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use HandlesEisenhowerMatrix;
    /**
     * Display a listing of tasks for a team
     */
        $isCoordinator = $team->isCoordinator(auth()->user());
        $tasks = $team->tasks()
            ->visibleTo(auth()->user(), $isCoordinator)
            ->operationalFor(auth()->user(), $team)
            ->with(['assignedUser', 'tags', 'creator'])
            ->orderBy('due_date', 'asc')
            ->orderBy('priority', 'desc')
            ->paginate(20);

        return view('tasks.index', compact('team', 'tasks'));
    }

    /**
     * Show the form for creating a new task
     */
    public function create(Team $team)
    {
        $users = $team->members;
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $tasks = $team->tasks()->orderBy('title')->get();

        return view('tasks.create', compact('team', 'users', 'groups', 'priorities', 'tasks'));
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
        ]);

        $isTemplate = !empty($validated['assigned_to']) || !empty($validated['assigned_groups']);

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
        ]);

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

        return redirect()->route('teams.tasks.show', [$team, $task])
            ->with('success', __('tasks.created'));
    }

    /**
     * Display the specified task
     */
    public function show(Team $team, Task $task)
    {
        $task->load(['assignedTo', 'assignedGroups', 'creator', 'histories', 'tags']);

        return view('tasks.show', compact('team', 'task'));
    }

    /**
     * Show the form for editing the task
     */
    public function edit(Team $team, Task $task)
    {
        $users = $team->members;
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $statuses = ['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada', 'blocked' => 'Bloqueada'];
        $tasks = $team->tasks()->where('id', '!=', $task->id)->orderBy('title')->get();

        return view('tasks.edit', compact('team', 'task', 'users', 'groups', 'priorities', 'statuses', 'tasks'));
    }

    /**
     * Update the task in storage
     */
    public function update(Request $request, Team $team, Task $task)
    {
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
        ]);

        // Store old values for history
        $oldValues = $task->getAttributes();

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
        ]);

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

        // Handle Assignments and Instances
        if ($request->has('title')) {
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
            
            if ($isTemplate) {
                // Calculate unique users
                $userIds = collect($assignedTo);
                foreach ($assignedGroups as $groupId) {
                    $group = $team->groups()->find($groupId);
                    if ($group) {
                        $userIds = $userIds->merge($group->users->pluck('id'));
                    }
                }
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

        return redirect()->route('teams.tasks.show', [$team, $task])
            ->with('success', __('tasks.updated'));
    }

    /**
     * Remove the task from storage
     */
    public function destroy(Team $team, Task $task)
    {
        $task->delete();

        return redirect()->route('teams.tasks.index', $team)
            ->with('success', __('tasks.deleted'));
    }

    /**
     * Get tasks by status (API endpoint for AJAX)
     */
    public function byStatus(Team $team, string $status)
    {
        $tasks = $team->tasks()
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
                ->with(['assignedTo', 'tags'])
                ->get()
                ->filter(function ($task) use ($q) {
                    return $this->getQuadrant($task) === $q;
                })
                ->values();
        }

        return response()->json($quadrants);
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
        ]);

        if ($request->has('scheduled_date') || $request->has('due_date')) {
            $updateData = [];
            if ($request->has('scheduled_date')) $updateData['scheduled_date'] = $validated['scheduled_date'];
            if ($request->has('due_date')) $updateData['due_date'] = $validated['due_date'];
            
            $task->update($updateData);

            // Sync with instances if it's a template
            if ($task->is_template) {
                $task->instances()->update($updateData);
            }
        }

        if ($request->has('quadrant') && $validated['quadrant'] !== null) {
            // Mapping quadrant back to priority/urgency
            $mapping = [
                1 => ['priority' => 'high', 'urgency' => 'high'],
                2 => ['priority' => 'high', 'urgency' => 'low'],
                3 => ['priority' => 'low', 'urgency' => 'high'],
                4 => ['priority' => 'low', 'urgency' => 'low'],
            ];

            $task->update([
                'priority' => $mapping[$validated['quadrant']]['priority'],
                'urgency' => $mapping[$validated['quadrant']]['urgency'],
                'status' => $validated['status'] ?? 'in_progress',
            ]);
        }

        if ($request->has('status')) {
            $oldStatus = $task->status;
            $status = $validated['status'];
            
            $updateData = ['status' => $status];
            
            if ($status === 'completed') {
                $updateData['progress_percentage'] = 100;
            } elseif ($status === 'pending' || $status === 'in_progress' || $status === 'blocked') {
                if ($task->progress_percentage === 100) {
                    $updateData['progress_percentage'] = 90; // Just below 100 to show it's not done
                }
            }

            $task->update($updateData);

            // Trigger notification if blocked
            if ($validated['status'] === 'blocked' && $oldStatus !== 'blocked') {
                $team->creator->notify(new \App\Notifications\TaskBlockedNotification($task, auth()->user()));
                // Also notify coordinators
                foreach ($team->members()->wherePivotIn('role_id', function ($q) {
                    $q->select('id')->from('team_roles')->where('name', 'coordinator');
                })->get() as $coordinator) {
                    if ($coordinator->id !== auth()->id()) {
                        $coordinator->notify(new \App\Notifications\TaskBlockedNotification($task, auth()->user()));
                    }
                }
            }

            // Check for milestones if it's an instance
            if ($task->isInstance() && $validated['status'] === 'completed') {
                $parent = $task->parent;
                
                // Calculate progress manually here to be sure
                $totalChildren = $parent->children()->count();
                $completedChildren = $parent->children()->where('status', 'completed')->count();
                $progress = $totalChildren > 0 ? ($completedChildren / $totalChildren) * 100 : 0;

                // AUTOMATIC COMPLETION: If all children are completed, mark parent as completed
                if ($completedChildren === $totalChildren && $parent->status !== 'completed') {
                    $parent->update([
                        'status' => 'completed',
                        'progress_percentage' => 100
                    ]);
                    
                    // Optional: Log completion in parent history
                    $parent->histories()->create([
                        'user_id' => auth()->id(),
                        'action' => 'automated_completion',
                        'new_values' => $parent->getAttributes(),
                    ]);
                }

                if (in_array((int)$progress, [50, 75, 100])) {
                    $team->creator->notify(new \App\Notifications\TaskMilestoneNotification($parent, (int)$progress));
                }
            }
        }
        if ($request->has('progress_percentage')) {
            $progress = (int) $validated['progress_percentage'];
            $updateData = ['progress_percentage' => $progress];

            if ($progress === 100) {
                $updateData['status'] = 'completed';
            } elseif ($progress < 100 && $task->status === 'completed') {
                $updateData['status'] = 'in_progress';
            }

            $task->update($updateData);
        }

        return response()->json(['success' => true]);
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
}
