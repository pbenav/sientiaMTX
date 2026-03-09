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
    public function index(Team $team)
    {
        return redirect()->route('teams.dashboard', $team);
    }

    /**
     * Show the form for creating a new task
     */
    public function create(Team $team)
    {
        $users = $team->members;
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];

        return view('tasks.create', compact('team', 'users', 'groups', 'priorities'));
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
        ]);

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
        ]);

        // Assign users if provided
        if (!empty($validated['assigned_to'])) {
            foreach ($validated['assigned_to'] as $userId) {
                $task->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }

        // Assign groups if provided
        if (!empty($validated['assigned_groups'])) {
            foreach ($validated['assigned_groups'] as $groupId) {
                $task->assignments()->create([
                    'group_id' => $groupId,
                    'assigned_by_id' => auth()->id(),
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
        $statuses = ['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'];

        return view('tasks.edit', compact('team', 'task', 'users', 'groups', 'priorities', 'statuses'));
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
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|array',
            'assigned_groups' => 'nullable|array',
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
        ]);

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

        // Update assignments if provided
        if (isset($validated['assigned_to']) || isset($validated['assigned_groups'])) {
            $task->assignments()->delete();
            
            if (!empty($validated['assigned_to'])) {
                foreach ($validated['assigned_to'] as $userId) {
                    $task->assignments()->create([
                        'user_id' => $userId,
                        'assigned_by_id' => auth()->id(),
                    ]);
                }
            }

            if (!empty($validated['assigned_groups'])) {
                foreach ($validated['assigned_groups'] as $groupId) {
                    $task->assignments()->create([
                        'group_id' => $groupId,
                        'assigned_by_id' => auth()->id(),
                    ]);
                }
            }
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
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
        ]);

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
        } elseif ($request->has('status') && $validated['status'] === 'completed') {
            $task->update(['status' => 'completed']);
        }

        return response()->json(['success' => true]);
    }
}
