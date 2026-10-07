<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;

class TeamDashboardService
{
    public function getDashboardData(Team $team, User $user, bool $isManager, ?int $skillId, bool $hideCompleted): array
    {
        $query = $team->activities()
            ->with([
                'assignedTo', 'assignedGroups', 'tags', 'assignedUser', 'skills', 'parent', 'creator', 'service',
                'children' => function($q) use ($user, $isManager) {
                    $q->visibleTo($user, $isManager);
                },
                'children.assignedUser'
            ])
            ->visibleTo($user, $isManager)
            ->notEphemeral()
            ->forMatrix()
            ->focusedFor($user, $team)
            ->when($skillId, function ($q, $skillId) {
                $q->where(function ($sq) use ($skillId) {
                    $sq->where('metadata->skill_id', $skillId)
                        ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
                });
            });

        if ($isManager) {
            $query->where(function ($q) use ($user) {
                $q->where(function ($backlog) {
                    $backlog->whereDoesntHave('assignedTo')
                            ->whereDoesntHave('assignedGroups')
                            ->whereNotExists(function ($sub) {
                                $sub->select(\DB::raw(1))
                                    ->from('activity_task_mapping')
                                    ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                    ->whereColumn('activity_task_mapping.activity_id', 'activities.id');
                            });
                })
                ->orWhere('created_by_id', $user->id)
                ->orWhereHas('assignedTo', fn($sq) => $sq->where('users.id', $user->id))
                ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            });
        }

        $allTasks = $query->get();

        $quadrants = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
        ];

        $completedLimit = (int) config('settings.kanban_completed_limit', 10);

        foreach ($allTasks as $task) {
            $isFinished = $task->isCompleted() || $task->status_value === 'deprecated' || $task->status_value === 'legacy' || $task->is_archived;
            if (!$isFinished) {
                $quadrant = $this->getQuadrant($task);
                $quadrants[$quadrant][] = $task;
            }
        }

        $completedTasks = $allTasks->filter(fn($t) => $t->isCompleted() || $t->status_value === 'deprecated' || $t->status_value === 'legacy' || $t->is_archived)
            ->sortByDesc('updated_at')
            ->take($completedLimit);

        foreach ($quadrants as &$qTasks) {
            usort($qTasks, function ($a, $b) {
                if ($a->matrix_order === null && $b->matrix_order === null) return 0;
                if ($a->matrix_order === null) return 1;
                if ($b->matrix_order === null) return -1;
                return $a->matrix_order <=> $b->matrix_order;
            });
        }
        unset($qTasks);

        return [
            'quadrants' => $quadrants,
            'tasks' => $allTasks,
            'completedTasks' => $completedTasks,
            'completedLimit' => $completedLimit,
        ];
    }

    private function getQuadrant($task): int
    {
        // Usa la lógica estandarizada del trait HandlesEisenhowerMatrix
        if (method_exists($task, 'getQuadrant')) {
            return $task->getQuadrant($task);
        }

        // Fallback porsia
        $priority = $task->priority;
        $urgency = $task->urgency ?? data_get($task->metadata ?? [], 'urgency', 'medium');
        
        $isPriority = in_array($priority, ['high', 'critical']);
        $isUrgent = in_array($urgency, ['high', 'critical']);

        if ($isPriority && $isUrgent) return 1;
        if ($isPriority && !$isUrgent) return 2;
        if (!$isPriority && $isUrgent) return 3;
        return 4;
    }
}
