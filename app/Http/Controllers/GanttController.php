<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Traits\HandlesPersistentFilters;

class GanttController extends Controller
{
    use HandlesPersistentFilters;
    /**
     * Show the Gantt chart view for a team
     */
    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $members = $team->members()->get();
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->get();

        // Get the exact task set that would be visible in the Gantt chart with current filters
        $tasks = $this->getTaskSet($request, $team);

        // Calculate Heat Bar (Action Density) for the current month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $actionHeat = [];
        $userId = auth()->id();

        // 2.1: Identify Leaf Tasks to avoid double counting effort (Master + Instance)
        $parentIds = $tasks->pluck('parent_id')->filter()->unique();
        $leafTasks = $tasks->filter(fn($t) => !$parentIds->contains($t->id));

        $filters = $this->getPersistentFilters($request, 'tasks', [
            'status', 'priority', 'assigned_to', 'skill_id', 'type', 'search'
        ]);

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $currentDay = $startOfMonth->copy()->addDays($i - 1);
            
            // Filter leaf tasks active this specific day from the already filtered set
            // and EXCLUDE completed/cancelled tasks from resilience calculation
            $dayTasks = $leafTasks->filter(function($t) use ($currentDay) {
                if (in_array($t->status, ['completed', 'cancelled'])) return false;

                $start = $t->scheduled_date ?? $t->created_at;
                $end = $t->due_date ?? $start;
                return $currentDay->between($start->startOfDay(), $end->endOfDay());
            });

            $actionHeat[$i] = [
                'weight' => $dayTasks->sum(fn($t) => $t->cognitive_load ?? 1),
                'user_weight' => $dayTasks->where('assigned_user_id', $userId)->sum(fn($t) => $t->cognitive_load ?? 1),
                'count' => $dayTasks->count(),
                'user_count' => $dayTasks->where('assigned_user_id', $userId)->count(),
            ];
        }

        return view('teams.gantt', compact('team', 'members', 'skills', 'actionHeat', 'daysInMonth', 'filters'));
    }

    public function data(Request $request, Team $team)
    {
        try {
            if (auth()->user()->cannot('view', $team)) {
                return response()->json(['error' => __('teams.unauthorized_access')], 403);
            }
            $tasks = $this->getTaskSet($request, $team);

            // Map to Frappe Gantt format
            $formattedTasks = $tasks->map(function (Task $task) use ($request) {
                $start = $task->scheduled_date ?: ($task->created_at ?: now());
                $end   = $task->due_date       ?: $start->copy()->addDay();
                $progress = $task->progress;

                // Distinguish template vs instance vs recurring in the label
                if ($task->is_template || $task->is_autoprogrammable) {
                    $label = ($task->is_autoprogrammable ? '🔄 ' : '📋 ') . $task->title;
                } elseif ($task->assignedUser) {
                    $label = '👤 ' . ($task->assignedUser->short_name ?: $task->assignedUser->name) . ': ' . $task->title;
                } else {
                    $label = $task->title;
                }

                if ($task->parent_id) $label = '   ↳ ' . $label;

                $typeClass = $task->is_template ? 'gantt-master' : ($task->parent_id ? 'gantt-instance' : 'gantt-plain');
                // Only templates should be forced readonly for regular members in this context
                $isReadonly = $task->is_template && auth()->user()->cannot('update', $task);
                $readonlyClass = $isReadonly ? 'gantt-readonly' : '';
                $colorClass = $task->getGanttColorClass();

                return [
                    'id'           => (string) $task->id,
                    'name'         => $label,
                    'start'        => $start->format('Y-m-d'),
                    'end'          => $end->format('Y-m-d'),
                    'progress'     => $progress,
                    'dependencies' => $task->metadata['dependency_id'] ?? ($task->parent_id ? (string) $task->parent_id : ''),
                    'custom_class' => "{$typeClass} {$colorClass} {$readonlyClass}",
                    'status'       => $task->status,
                    'status_label' => __("tasks.statuses.{$task->status}"),
                    'priority'     => $task->priority,
                    'priority_label' => __("tasks.priorities.{$task->priority}"),
                    'urgency'      => $task->urgency,
                    'is_template'  => $task->is_template,
                    'has_children' => $task->children->count() > 0,
                    'assigned_to'  => $task->assignedUser?->name ?? $task->creator?->name,
                    'user_name'    => $task->assignedUser?->name ?? ($task->creator?->name ?? 'Sin asignar'),
                    'user_initials' => ($task->assignedUser ?: $task->creator) 
                                        ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(($task->assignedUser ?: $task->creator)->name, 0, 2)) 
                                        : '??',
                    'user_id'      => $task->assigned_user_id ?? $task->created_by_id,
                    'weight'       => $task->cognitive_load ?? 1,
                    'parent_id'    => $task->parent_id,
                    'parent_title' => $task->parent?->title,
                    'readonly'     => auth()->user()->cannot('update', $task),
                    'skills'       => $task->skills->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray(),
                    'members_progress' => (function() use ($task, $request) {
                        $user = auth()->user();
                        $showCompleted = !session('hide_completed_tasks', true) || $request->status;
                        
                        if ($task->children->count() > 0) {
                            return $task->children
                                ->filter(function($c) use ($showCompleted, $user, $task) {
                                    // Must match the exclusion logic in getTaskSet (Step 2)
                                    if (!$showCompleted && in_array($c->status, ['completed', 'cancelled'])) return false;
                                    
                                    // Redundancy rule: if we are viewing the master and this child is ours, 
                                    // it's already represented by the master row, so we skip it in the sub-breakdown
                                    if ($task->is_template && $c->assigned_user_id === $user->id) return false;
                                    
                                    return true;
                                })
                                ->map(fn($child) => [
                                    'name' => $child->assignedUser?->short_name ?: ($child->assignedUser?->name ?: 'Desconocido'),
                                    'progress' => $child->progress,
                                    'time_human' => null,
                                    'initials' => $child->assignedUser 
                                        ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($child->assignedUser->name, 0, 2)) 
                                        : '??'
                                ])
                                ->sortBy(function($m) {
                                    // Normalize for sorting: remove emojis, accents and lowercase
                                    $name = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $m['name']);
                                    return mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', trim($name)));
                                }, SORT_NATURAL)
                                ->values()->toArray();
                        }
                        
                        if ($task->assignedTo->count() > 1 || $task->timeLogs->count() > 0) {
                            return $task->assignedTo->merge($task->timeLogs->pluck('user')->filter())
                                ->unique('id')
                                ->map(function($user) use ($task) {
                                    $seconds = (int) $task->timeLogs->where('user_id', $user->id)->whereNotNull('end_at')->sum(fn($log) => $log->start_at->diffInSeconds($log->end_at));
                                    $hours = floor($seconds / 3600);
                                    $minutes = floor(($seconds % 3600) / 60);
                                    return [
                                        'name' => $user->short_name ?: $user->name,
                                        'progress' => null,
                                        'time_human' => $seconds > 0 ? "{$hours}h {$minutes}m" : "0h 0m",
                                        'initials' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 2))
                                    ];
                                })
                                ->sortBy(function($m) {
                                    $name = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $m['name']);
                                    return mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', trim($name)));
                                }, SORT_NATURAL)
                                ->values()->toArray();
                        }
                        
                        return [];
                    })(),
                ];
            });

            return response()->json($formattedTasks);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Gantt Data Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Shared logic to get the filtered task set for Gantt
     */
    private function getTaskSet(Request $request, Team $team)
    {
        $user      = auth()->user();
        $isManager = $team->isManager($user);

        // Step 1: Base operational set (visibility)
        $baseTasks = $team->tasks()
            ->with([
                'parent', 'assignedUser', 'creator', 'skills',
                'children' => function($q) use ($user, $isManager) {
                    $q->visibleTo($user, $isManager);
                }
            ])
            ->visibleTo($user, $isManager)
            ->operationalFor($user, $team, true)
            ->get();

        // Step 2: Expansion rules
        $ganttTaskIds = collect();
        $showCompleted = !session('hide_completed_tasks', true) || $request->status;

        foreach ($baseTasks as $task) {
            $isTaskCompleted = in_array($task->status, ['completed', 'cancelled']);

            // Only add the task itself if it's not completed OR we are showing completed tasks
            if ($showCompleted || !$isTaskCompleted) {
                $ganttTaskIds->push($task->id);
            }

            if ($task->is_template && $team->isCoordinator($user)) {
                // For coordinators, we push children EXCEPT their own (to avoid redundancy with the master bar)
                $task->children->each(function ($child) use ($ganttTaskIds, $showCompleted, $user) {
                    if ($showCompleted || !in_array($child->status, ['completed', 'cancelled'])) {
                        // Skip my own instance if I'm already seeing the master
                        if ($child->assigned_user_id !== $user->id) {
                            $ganttTaskIds->push($child->id);
                        }
                    }
                });
            } elseif ($task->isInstance()) {
                // REDUNDANCY RULE: Only pull the parent if we are NOT the assignee 
                // Members see their instance but hide the master.
                $isMyAssignedInstance = ($task->assigned_user_id === $user->id);
                
                if (!$isMyAssignedInstance) {
                    if ($showCompleted || !$isTaskCompleted) {
                        $ganttTaskIds->push($task->parent_id);
                    }
                }
            }
        }
    
        // Step 2.1: ENSURE HIERARCHY INTEGRITY
        // If we are showing a child, we MUST show its parent (even if completed/hidden) 
        // to maintain the grouping structure in the Gantt UI.
        $parentIds = Task::whereIn('id', $ganttTaskIds->filter())->whereNotNull('parent_id')->pluck('parent_id');
        $ganttTaskIds = $ganttTaskIds->merge($parentIds);

        if ($team->isModerator($user)) {
             $templateIds = $team->tasks()->where('is_template', true)->pluck('id');
             $ganttTaskIds = $ganttTaskIds->merge($templateIds);
        }

        $uniqueIds = $ganttTaskIds->filter()->unique()->values();

        // Step 3: Filters
        $filters = $this->getPersistentFilters($request, 'tasks', [
            'status', 'priority', 'assigned_to', 'skill_id', 'type', 'search'
        ]);

        $query = Task::with(['parent', 'assignedUser', 'skills', 'assignedTo', 'timeLogs.user'])
            ->whereIn('id', $uniqueIds)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['skill_id'] ?? null, function ($q, $skillId) {
                $q->where(function ($sq) use ($skillId) {
                    $sq->where('skill_id', $skillId)
                        ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
                });
            })
            ->when($filters['priority'] ?? null, fn($q, $priority) => $q->where('priority', $priority))
            ->when($filters['assigned_to'] ?? null, fn($q, $assignedTo) => $q->where('assigned_user_id', $assignedTo))
            ->when($filters['type'] ?? null, function ($q, $type) {
                if ($type === 'template') {
                    $q->where('is_template', true);
                } elseif ($type === 'instance') {
                    $q->where('is_template', false)->whereNotNull('parent_id');
                } elseif ($type === 'plain') {
                    $q->where('is_template', false)->whereNull('parent_id');
                }
            });

        // Apply Time Range Filter and ensure we keep parents of matching tasks to avoid dependency crashes
        $range = $request->get('time_range', '3');
        if ($range !== 'all') {
            $months = (int) $range;
            $startRange = now()->subMonths(1)->startOfMonth();
            $endRange = now()->addMonths($months - 1)->endOfMonth();

            $matchingIds = (clone $query)->where(function($sq) use ($startRange, $endRange) {
                $sq->where(function($dateQ) use ($startRange, $endRange) {
                    $dateQ->whereBetween('scheduled_date', [$startRange, $endRange])
                          ->orWhereBetween('due_date', [$startRange, $endRange]);
                })->orWhere(function($fallbackQ) use ($startRange, $endRange) {
                    $fallbackQ->whereNull('scheduled_date')
                              ->whereBetween('created_at', [$startRange, $endRange]);
                });
            })->pluck('id');

            // Include parents of matching tasks even if they are out of range
            $parentIds = Task::whereIn('id', $matchingIds)->whereNotNull('parent_id')->pluck('parent_id')->unique();
            $finalSetIds = $matchingIds->merge($parentIds)->unique();
            
            $query->whereIn('id', $finalSetIds);
        }

        $allGanttTasks = $query
            ->when($request->limit && $request->limit !== 'all', function ($q) use ($request) {
                $q->limit((int) $request->limit);
            })
            ->get();

        // Step 4: Sorting (Grouped by Master/Parent, then by Member Name)
        return $allGanttTasks->sortBy(function ($task) {
            $groupId = $task->parent_id ?? $task->id;
            $isChild = $task->parent_id ? 1 : 0;
            
            // For sorting by member name within the group
            $memberName = $task->assignedUser?->short_name ?: ($task->assignedUser?->name ?: 'ZZZ');
            $normalizedMember = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', trim($memberName)));
            
            // Format: [GroupID] - [MasterFirst] - [MemberName] - [TaskID]
            return sprintf('%010d-%d-%s-%010d', $groupId, $isChild, $normalizedMember, $task->id);
        })->values();
    }
}
