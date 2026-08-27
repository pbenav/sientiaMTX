<?php

namespace App\Actions\Kanban;

use App\Enums\TaskPriority;
use App\Models\Activity;
use App\Models\KanbanColumn;
use App\Models\Skill;
use App\Models\Team;
use Illuminate\Http\Request;

class BuildKanbanDataAction
{
    /**
     * Builds all data needed for the Kanban board view.
     *
     * @param Team $team
     * @param Request $request
     * @param \App\Models\User $user
     * @param bool $isManager
     * @return array
     */
    public function execute(Team $team, Request $request, $user, bool $isManager): array
    {
        $filters = $this->getFilters($request);

        $columns = $this->buildColumns($team, $user, $isManager, $filters);
        $todayActivities = $this->buildTodayActivities($team, $user, $isManager);
        $completedTasks = $this->buildCompletedTasks($team, $user, $isManager);

        // Ensure columns have default colors
        $columns->each(function ($column) {
            if (!$column->color) {
                $column->color = $this->defaultColorForType($column->type);
                $column->save();
            }
        });

        return [
            'columns' => $columns,
            'todayActivities' => $todayActivities,
            'completedTasks' => $completedTasks,
            'filters' => $filters,
            'members' => $team->members,
            'skills' => Skill::forTeamOrGlobal($team->id)->get(),
            'expedientes' => $team->expedientes()->orderBy('created_at', 'desc')->get(),
        ];
    }

    /**
     * Builds columns with their filtered activities.
     */
    protected function buildColumns(Team $team, $user, bool $isManager, array $filters)
    {
        return $team->kanbanColumns()
            ->with(['activities' => function ($query) use ($team, $user, $isManager, $filters) {
                $this->applyActivityFilters($query, $team, $user, $isManager, $filters);
            }])
            ->orderBy('order_index', 'asc')
            ->get();
    }

    /**
     * Applies all filter scopes to an activity query.
     */
    protected function applyActivityFilters($query, Team $team, $user, bool $isManager, array $filters): void
    {
        $query->with([
            'expediente',
            'assignedUser',
            'assignedTo',
            'skills',
            'tags',
            'instances' => fn($q) => $q->where('is_template', false)->orderBy('title'),
            'attachments',
            'timeLogs' => fn($q) => $q->whereDate('start_at', today()),
        ])
        ->forKanban()
        ->visibleTo($user, $isManager)
        ->operationalForKanban($user, $team)
        ->notEphemeral()
        ->where('is_archived', false)
        ->when($filters['status'] ?? null, fn($q, $s) => $q->whereJsonContains('status->value', $s))
        ->when($filters['priority'] ?? null, fn($q, $p) => $q->where('priority', $p))
        ->when($filters['assigned_to'] ?? null, function ($q, $a) {
            $q->where(function ($sq) use ($a) {
                $sq->whereHas('assignedTo', fn($sub) => $sub->where('users.id', $a))
                   ->orWhereExists(function ($subq) use ($a) {
                       $subq->select(\DB::raw(1))
                            ->from('activity_task_mapping')
                            ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                            ->whereColumn('activity_task_mapping.activity_id', 'activities.id')
                            ->where('task_assignments.user_id', $a);
                   });
            });
        })
        ->when($filters['search'] ?? null, function ($q, $s) {
            $q->where('title', 'like', "%{$s}%")
              ->orWhere('description', 'like', "%{$s}%");
        })
        ->when($filters['expediente_id'] ?? null, fn($q, $expId) => $q->where('expediente_id', $expId))
        ->when($filters['skill_id'] ?? null, function ($q, $skillId) {
            $q->where(function ($sq) use ($skillId) {
                $sq->where('metadata->skill_id', $skillId)
                   ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
            });
        })
        ->when($filters['type'], fn($q, $type) => $this->filterByType($q, $type))
        ->when($filters['urgency'] ?? null, fn($q, $u) => $q->byUrgency($u))
        ->when($filters['assignment_mode'] ?? null, fn($q, $m) => $q->byAssignmentMode($m))
        ->when($filters['blocked'] ?? null, fn($q) => $q->blocked())
        ->orderBy('kanban_order', 'asc')
        ->orderByRaw(TaskPriority::orderQuery())
        ->orderBy('progress_percentage', 'desc');
    }

    /**
     * Filters activities by type (template, instance, plain).
     */
    protected function filterByType($query, string $type): void
    {
        match ($type) {
            'template' => $query->where('is_template', true),
            'instance' => $query->where('is_template', false)->whereNotNull('parent_id'),
            'plain' => $query->where('is_template', false)->whereNull('parent_id'),
            default => $query->where('type', $type),
        };
    }

    /**
     * Builds today's and overdue activities.
     */
    protected function buildTodayActivities(Team $team, $user, bool $isManager)
    {
        return $team->activities()
            ->with([
                'expediente',
                'assignedUser',
                'assignedTo',
                'skills',
                'tags',
                'instances' => fn($q) => $q->where('is_template', false)->orderBy('title'),
                'attachments',
                'timeLogs' => fn($q) => $q->whereDate('start_at', today()),
            ])
            ->forKanban()
            ->operationalForKanban($user, $team)
            ->notEphemeral()
            ->where('is_archived', false)
            ->today()
            ->orderBy('kanban_order', 'asc')
            ->orderByRaw(TaskPriority::orderQuery())
            ->get();
    }

    /**
     * Builds recently completed (archived) tasks.
     */
    protected function buildCompletedTasks(Team $team, $user, bool $isManager)
    {
        return $team->activities()
            ->with(['expediente', 'assignedUser'])
            ->whereIn('type', Activity::KANBAN_TYPES)
            ->visibleTo($user, $isManager)
            ->notEphemeral()
            ->where('is_archived', true)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Returns the default color for a column type.
     */
    protected function defaultColorForType(string $type): string
    {
        return match ($type) {
            'todo' => '#fee2e2',
            'in_progress' => '#dbeafe',
            'done' => '#dcfce7',
            default => '#f9fafb',
        };
    }

    /**
     * Extracts and returns filters from the request.
     */
    protected function getFilters(Request $request): array
    {
        return app(\App\Services\PersistentFilterService::class)->getFilters($request, 'tasks', [
            'status', 'priority', 'assigned_to', 'skill_id', 'type', 'search', 'expediente_id',
            'urgency', 'assignment_mode', 'blocked',
        ]);
    }
}
