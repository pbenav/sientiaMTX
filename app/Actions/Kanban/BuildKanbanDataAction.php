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
        $filters = $this->getFilters($request, $team);
        $today = today();

        $columns = $this->buildColumns($team, $user, $isManager, $filters, $today);
        $todayActivities = collect(); // Obsoleto: eliminada la columna fija 'Hoy' para evitar duplicar tareas
        $completedTasks = $this->buildCompletedTasks($team, $user, $isManager, $filters);

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
            'skills' => Skill::forTeam($team->id)->get(),
            'expedientes' => $team->expedientes()->orderBy('created_at', 'desc')->get(),
        ];
    }

    /**
     * Builds columns with their filtered activities.
     */
    protected function buildColumns(Team $team, $user, bool $isManager, array $filters, $today = null)
    {
        $today = $today ?? today();

        return $team->kanbanColumns()
            ->with(['activities' => function ($query) use ($team, $user, $isManager, $filters, $today) {
                $this->applyActivityFilters($query, $team, $user, $isManager, $filters, $today);
            }])
            ->orderBy('order_index', 'asc')
            ->get();
    }

    /**
     * Applies all filter scopes to an activity query.
     */
    protected function applyActivityFilters($query, Team $team, $user, bool $isManager, array $filters, $today = null): void
    {
        $today = $today ?? today();

        $query->with([
            'expediente',
            'assignedUser',
            'assignedTo',
            'skills',
            'tags',
            'notes' => fn($q) => $q->where('visibility', 'private')->select('id', 'activity_id', 'visibility'),
            'instances' => fn($q) => $q->where('is_template', false)->orderBy('title'),
            'attachments',
            'timeLogs' => fn($q) => $q->whereDate('start_at', $today),
        ])
        ->withCount(['children', 'instances'])
        ->where('activities.team_id', $team->id)
        ->forKanban()
        ->notEphemeral()
        ->where('activities.is_archived', false)
        ->visibleTo($user, $isManager);

        if (!empty($filters['assigned_to']) && $filters['assigned_to'] === 'all') {
            // Ver todas las actividades operativas del equipo (filtradas por visibleTo)
            $query->operationalForTeam($team);
        } elseif (!empty($filters['assigned_to'])) {
            $targetUser = \App\Models\User::find($filters['assigned_to']);
            if ($targetUser) {
                $query->operationalForKanban($targetUser, $team);
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            // Por defecto SIEMPRE el tablero operativo personal del usuario actual (incluso si es manager)
            $query->operationalForKanban($user, $team);
        }

        $query->when($filters['status'] ?? null, fn($q, $s) => $q->whereJsonContains('status->value', $s))
            ->when($filters['priority'] ?? null, fn($q, $p) => $q->where('priority', $p))
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($sq) use ($s) {
                    $sq->where('title', 'like', "%{$s}%")
                       ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->when($filters['expediente_id'] ?? null, fn($q, $expId) => $q->where('expediente_id', $expId))
            ->when($filters['skill_id'] ?? null, function ($q, $skillId) {
                $q->where(function ($sq) use ($skillId) {
                    $sq->where('metadata->skill_id', $skillId)
                       ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
                });
            })
            ->when($filters['type'] ?? null, fn($q, $type) => $this->filterByType($q, $type))
            ->when($filters['urgency'] ?? null, fn($q, $u) => $q->byUrgency($u))
            ->when($filters['assignment_mode'] ?? null, fn($q, $m) => $q->byAssignmentMode($m))
            ->when(isset($filters['blocked']) && $filters['blocked'] !== '' && $filters['blocked'] !== null, function ($q) use ($filters) {
                if ($filters['blocked'] == '1') {
                    $q->blocked();
                } elseif ($filters['blocked'] == '0') {
                    $q->whereJsonDoesntContain('status->value', 'blocked');
                }
            })
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
    protected function buildTodayActivities(Team $team, $user, bool $isManager, array $filters = [], $today = null)
    {
        $today = $today ?? today();

        $query = $team->activities()
            ->with([
                'expediente',
                'assignedUser',
                'assignedTo',
                'skills',
                'tags',
                'notes' => fn($q) => $q->where('visibility', 'private')->select('id', 'activity_id', 'visibility'),
                'instances' => fn($q) => $q->where('is_template', false)->orderBy('title'),
                'attachments',
                'timeLogs' => fn($q) => $q->whereDate('start_at', $today),
            ])
            ->withCount(['children', 'instances'])
            ->forKanban()
            ->notEphemeral()
            ->where('activities.is_archived', false)
            ->visibleTo($user, $isManager)
            ->today();

        if (!empty($filters['assigned_to']) && $filters['assigned_to'] === 'all') {
            $query->operationalForTeam($team);
        } elseif (!empty($filters['assigned_to'])) {
            $targetUser = \App\Models\User::find($filters['assigned_to']);
            if ($targetUser) {
                $query->operationalForKanban($targetUser, $team);
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            $query->operationalForKanban($user, $team);
        }

        return $query->orderBy('kanban_order', 'asc')
            ->orderByRaw(TaskPriority::orderQuery())
            ->get();
    }

    /**
     * Builds recently completed (archived) tasks.
     */
    protected function buildCompletedTasks(Team $team, $user, bool $isManager, array $filters = [])
    {
        $userId = $user->id;

        $query = $team->activities()
            ->with(['expediente', 'assignedUser'])
            ->whereIn('type', Activity::KANBAN_TYPES)
            ->where('activities.is_archived', true)
            ->visibleTo($user, $isManager)
            ->notEphemeral();

        if (!empty($filters['assigned_to']) && $filters['assigned_to'] === 'all') {
            // Todas las archivadas completadas visibles para el usuario en este equipo
        } elseif (!empty($filters['assigned_to'])) {
            $targetId = (int) $filters['assigned_to'];
            $query->where(function ($q) use ($targetId) {
                $q->whereHas('assignedTo', fn($sq) => $sq->where('users.id', $targetId))
                  ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $targetId)));
            });
        } else {
            $query->where(function ($q) use ($userId) {
                $q->whereHas('assignedTo', fn($sq) => $sq->where('users.id', $userId))
                  ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                  ->orWhere(function ($own) use ($userId) {
                      $own->where('created_by_id', $userId)
                          ->whereNull('parent_id')
                          ->whereDoesntHave('assignedTo', fn($at) => $at->where('users.id', '!=', $userId))
                          ->whereDoesntHave('assignedGroups');
                  });
            });
        }

        return $query->orderBy('updated_at', 'desc')
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
    protected function getFilters(Request $request, Team $team): array
    {
        return app(\App\Services\PersistentFilterService::class)->getFilters($request, "tasks_team_{$team->id}", [
            'status', 'priority', 'assigned_to', 'skill_id', 'type', 'search', 'expediente_id',
            'urgency', 'assignment_mode', 'blocked',
        ]);
    }
}
