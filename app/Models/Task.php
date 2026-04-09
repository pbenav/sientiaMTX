<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Str;

use App\Traits\HandlesEisenhowerMatrix;

class Task extends Model
{
    use HasFactory, SoftDeletes, HandlesEisenhowerMatrix;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (self $model) {
            // Unarchive tasks that are not 100% completed
            if ($model->isDirty('progress_percentage') || $model->isDirty('status')) {
                if ($model->progress_percentage < 100 && $model->status === 'completed') {
                    $model->status = 'in_progress';
                    $model->is_archived = false;
                } elseif ($model->progress_percentage == 100 && !in_array($model->status, ['completed', 'cancelled'])) {
                    $model->status = 'completed';
                }
            }

            // Sync archived status: If not completed, it should NOT be archived
            if ($model->status !== 'completed' && $model->is_archived) {
                $model->is_archived = false;
            }
        });
    }


    protected $fillable = [
        'team_id',
        'title',
        'description',
        'priority',
        'urgency',
        'status',
        'scheduled_date',
        'due_date',
        'original_due_date',
        'created_by_id',
        'metadata',
        'observations',
        'parent_id',
        'is_template',
        'assigned_user_id',
        'progress_percentage',
        'visibility',
        'google_task_id',
        'google_task_list_id',
        'google_synced_at',
        'is_archived',
        'kanban_column_id',
        'kanban_order',
        'autoprogram_settings',
        'is_out_of_skill_tree',
        'cognitive_load',
        'is_backstage',
        'impact_human_metric',
        'skill_id',
        'matrix_order',
    ];
 
    protected $casts = [
        'metadata' => 'array',
        'due_date' => 'datetime',
        'scheduled_date' => 'datetime',
        'original_due_date' => 'datetime',
        'google_synced_at' => 'datetime',
        'is_archived' => 'boolean',
        'autoprogram_settings' => 'array',
        'is_out_of_skill_tree' => 'boolean',
        'cognitive_load' => 'integer',
        'is_backstage' => 'boolean',
        'impact_human_metric' => 'integer',
        'skill_id' => 'integer',
    ];

    // Relationship: A task belongs to a team
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Relationship: A task was created by a user
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Relationship: A task has many assignments
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    // Relationship: A task is assigned to many users
    public function assignedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    // Relationship: A task has one calendar event
    public function calendarEvent(): HasOne
    {
        return $this->hasOne(CalendarEvent::class);
    }

    // Relationship: A task has many history records
    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    // Relationship: A task has many tags
    public function tags(): HasMany
    {
        return $this->hasMany(TaskTag::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    // Relationship: A task can have a parent task (dependency)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    // Relationship: A task can have many subtasks or dependent tasks
    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    // Relationship: A template task has many instances
    public function instances(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->where('is_template', false);
    }

    // Relationship: An instance task belongs to a user
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Check if this task is an instance of a global task
     */
    public function isInstance(): bool
    {
        return !empty($this->parent_id) && !$this->is_template;
    }

    /**
     * Get the progress percentage for template tasks
     */
    public function getProgressAttribute(): int
    {
        // If it has children (subtasks or instances), calculate aggregate progress
        if ($this->children()->exists()) {
            $totalCount = $this->children()->count();
            if ($totalCount === 0) return 0;

            $totalProgress = $this->children()->sum('progress_percentage');
            return (int) round($totalProgress / $totalCount);
        }

        // For individual tasks, return the manual progress percentage
        // If status is completed, it should be 100 anyway, but we return the column value
        return (int) ($this->attributes['progress_percentage'] ?? ($this->status === 'completed' ? 100 : 0));
    }

    /**
     * Get the associated forum thread.
     */
    public function forumThread(): HasOne
    {
        return $this->hasOne(ForumThread::class);
    }

    /**
     * Get the CSS class for Frappe Gantt based on Eisenhower quadrant
     */
    public function getGanttColorClass(): string
    {
        $quadrant = $this->getQuadrant($this);
        return "gantt-q{$quadrant}";
    }

    // Scopes
    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
            ->where('status', '!=', 'completed');
    }

    public function scopeVisibleTo($query, $user, $isManager = false)
    {
        return $query->where(function($q) use ($user, $isManager) {
            // 1. Coordinator Override: Coordinators see EVERYTHING in the team
            if ($isManager) {
                return $q->where('team_id', $user->current_team_id ?? $q->getQuery()->from === 'tasks' ? 'tasks.team_id' : 'team_id');
            }

            // 2. Any team member can see PUBLIC tasks
            $q->where('visibility', 'public')
            // 3. Owners (creators) can see their OWN tasks (public or private)
              ->orWhere('created_by_id', $user->id)
            // 4. Directly assigned users or collaborators can see tasks assigned to them
              ->orWhere('assigned_user_id', $user->id)
              ->orWhereHas('assignedTo', function($subq) use ($user) {
                  $subq->where('users.id', $user->id);
              })
              ->orWhereHas('assignedGroups.users', function($subq) use ($user) {
                  $subq->where('users.id', $user->id);
              });
        });
    }

    /**
     * Scope for "What I should be working on or managing right now".
     * This handles the hierarchy to avoid showing both master and instance.
     */
    public function scopeOperationalFor($query, $user, Team $team)
    {
        $isCoordinator = $team->isCoordinator($user);

        return $query->where(function ($main) use ($user, $isCoordinator) {
            if ($isCoordinator) {
                // MANAGEMENT VIEW: Project Skeleton (All Tier 0 and Tier 1)
                $main->whereNull('parent_id')
                     ->orWhere('is_template', true);
            } else {
                // EXECUTION VIEW: My assigned work + its skeleton
                $main->where('assigned_user_id', $user->id)
                     ->orWhereHas('assignedTo', fn ($as) => $as->where('users.id', $user->id))
                     ->orWhere(function ($own) use ($user) {
                         $own->where('created_by_id', $user->id)
                             ->whereNull('parent_id');
                     });
            }
        });
    }

    /**
     * Specialized scope for focused views (Kanban/Matrix).
     * Filters for actionable items and applies deduplication for managers.
     */
    public function scopeFocusedFor($query, $user, Team $team)
    {
        $userId = $user->id;
        $isCoordinator = $team->isCoordinator($user);

        return $query->where(function ($q) use ($userId, $isCoordinator) {
            // 1. My assigned tasks (Actionable items)
            $q->where(function($assigned) use ($userId) {
                $assigned->where('assigned_user_id', $userId)
                         ->orWhereHas('assignedTo', fn($sq) => $sq->where('users.id', $userId));
            });

            // 2. For Coordinators: Also see general templates to manage them
            if ($isCoordinator) {
                $q->orWhere(function($mgmt) use ($userId) {
                    $mgmt->where('is_template', true)
                         ->orWhere(function($roots) {
                             $roots->whereNull('parent_id')
                                   ->whereDoesntHave('children');
                         });
                });
            }
        })
        // 3. DEDUPLICATION (The Silk Rule)
        // If I have an instance assigned, hide the parent (it's redundant for personal action)
        ->whereDoesntHave('children', function ($q) use ($userId) {
            $q->where('assigned_user_id', $userId);
        });
    }

    /**
     * Specialized scope for the Kanban board.
     * Legacy wrapper for scopeFocusedFor.
     */
    public function scopeOperationalForKanban($query, $user, Team $team)
    {
        return $this->scopeFocusedFor($query, $user, $team)
                    ->where('is_template', false);
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', '!=', 'completed');
    }
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function kanbanColumn(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class);
    }

    /**
     * Synchronize the Kanban column based on current progress and status.
     */
    public function syncKanbanColumn(): void
    {
        $team = $this->team;

        // Fallback: If relationship is not loaded but team_id exists, try to find it
        if (!$team && $this->team_id) {
            $team = Team::find($this->team_id);
        }

        if (!$team) {
            return;
        }

        $type = 'in_progress';
        $currentProgress = $this->progress;

        if ($currentProgress == 100 || $this->status === 'completed') {
            $type = 'done';
        } elseif ($this->status === 'in_progress') {
            $type = 'in_progress';
        } elseif ($currentProgress == 0 && ($this->status === 'pending' || $this->status === 'todo')) {
            $type = 'todo';
        }

        $column = $team->kanbanColumns()
            ->where('type', $type)
            ->orderBy('order_index')
            ->first();

        if ($column && $this->kanban_column_id !== $column->id) {
            $this->kanban_column_id = $column->id;
            $this->saveQuietly();
        }
    }

    /**
     * Generate occurrences for an autoprogrammable task.
     */
    public function generateOccurrences(): void
    {
        $settings = $this->autoprogram_settings;
        $frequency = $settings['frequency'] ?? 'daily';
        $interval = (int)($settings['interval'] ?? 1);
        $limitType = $settings['limit_type'] ?? 'count';
        $limitValue = $settings['limit_value'] ?? 1;
        $sequential = $settings['sequential'] ?? false;
        $skipWeekends = $settings['skip_weekends'] ?? false;
        $leadValue = (int)($settings['lead_value'] ?? 7);
        $leadUnit = $settings['lead_unit'] ?? 'days';

        $lastOccurrence = $this->children()->whereNotNull('scheduled_date')->orderBy('scheduled_date', 'desc')->first();
        
        // If we already reached the limit based on count
        if ($limitType === 'count' && $this->children()->count() >= (int)$limitValue) {
            $this->update(['is_autoprogrammable' => false]);
            return;
        }

        // Calculate the Date for the next occurrence
        if (!$lastOccurrence) {
            // Use the master task date as the base for the first occurrence child
            $currentDate = $this->scheduled_date ? $this->scheduled_date->copy() : now();
        } else {
            $currentDate = $lastOccurrence->scheduled_date->copy();
            switch ($frequency) {
                case 'daily': $currentDate->addDays($interval); break;
                case 'weekly': $currentDate->addWeeks($interval); break;
                case 'monthly': $currentDate->addMonths($interval); break;
                case 'yearly': $currentDate->addYears($interval); break;
            }
        }

        if ($skipWeekends && $currentDate->isWeekend()) {
            $currentDate->next(Carbon::MONDAY);
        }

        // Check end date limit
        if ($limitType === 'date' && $currentDate->greaterThan(Carbon::parse($limitValue))) {
            $this->update(['is_autoprogrammable' => false]);
            return;
        }

        // Duration relative to the master task
        $masterScheduled = $this->scheduled_date ? $this->scheduled_date->copy() : now();
        $masterDue = $this->due_date ? $this->due_date->copy() : $masterScheduled->copy()->addDay();
        $durationInSeconds = $masterScheduled->diffInSeconds($masterDue);

        // Create the Occurrence Task
        $occurrence = $this->replicate(['uuid', 'google_task_id', 'google_synced_at']);
        $occurrence->parent_id = $this->id;
        $occurrence->is_autoprogrammable = false;
        $occurrence->autoprogram_settings = null;
        $occurrence->scheduled_date = $currentDate->copy();
        $occurrence->due_date = $currentDate->copy()->addSeconds($durationInSeconds);
        $occurrence->status = 'pending';
        $occurrence->progress_percentage = 0;
        
        // Handle Sequential Dependency (Point to the last child in the chain)
        if ($sequential && $lastOccurrence) {
            $occurrence->metadata = array_merge($occurrence->metadata ?? [], ['dependency_id' => $lastOccurrence->id]);
        }

        $occurrence->save();

        // Trigger Instance Generation if this occurrence is a template
        if ($occurrence->is_template) {
            $this->spawnInstancesForOccurrence($occurrence);
        }

        // Update next_occurrence_at for the master task to optimize the command
        $nextDate = $currentDate->copy();
        switch ($frequency) {
            case 'daily': $nextDate->addDays($interval); break;
            case 'weekly': $nextDate->addWeeks($interval); break;
            case 'monthly': $nextDate->addMonths($interval); break;
            case 'yearly': $nextDate->addYears($interval); break;
        }
        if ($skipWeekends && $nextDate->isWeekend()) $nextDate->next(Carbon::MONDAY);

        $newSettings = $this->autoprogram_settings;
        $newSettings['next_occurrence_at'] = $nextDate->toDateTimeString();
        $this->update(['autoprogram_settings' => $newSettings]);
    }

    /**
     * Helper to spawn individual instances for a specific occurrence.
     */
    protected function spawnInstancesForOccurrence(Task $occurrence): void
    {
        $assignments = $this->assignments()->get();
        $userIds = collect();

        foreach ($assignments as $assignment) {
            if ($assignment->user_id) {
                $userIds->push($assignment->user_id);
            } elseif ($assignment->group_id) {
                $group = Group::find($assignment->group_id);
                if ($group) {
                    $userIds = $userIds->merge($group->users->pluck('id'));
                }
            }
        }

        $userIds->push($this->created_by_id);
        $uniqueUserIds = $userIds->unique();

        foreach ($uniqueUserIds as $userId) {
            $occurrence->children()->create([
                'team_id' => $occurrence->team_id,
                'title' => $occurrence->title,
                'description' => $occurrence->description,
                'priority' => $occurrence->priority,
                'urgency' => $occurrence->urgency,
                'status' => 'pending',
                'scheduled_date' => $occurrence->scheduled_date,
                'due_date' => $occurrence->due_date,
                'original_due_date' => $occurrence->due_date,
                'created_by_id' => $occurrence->created_by_id,
                'parent_id' => $occurrence->id,
                'is_template' => false,
                'assigned_user_id' => $userId,
                'visibility' => 'private',
            ]);
        }
    }

    // Time Tracking Relationships
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Get total time spent on this task and its children in seconds.
     */
    public function totalTrackedSeconds(): int
    {
        // Own logs
        $ownSeconds = (int) $this->timeLogs()->whereNotNull('end_at')->get()
            ->sum(fn($log) => $log->start_at->diffInSeconds($log->end_at));

        // Children logs (for template/parent tasks)
        $childrenSeconds = 0;
        if ($this->children()->exists()) {
             // Efficiently calculate time from all descendants
             $childrenIds = $this->children()->pluck('id');
             $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)->whereNotNull('end_at')->get();
             $childrenSeconds = (int) $childrenLogs->sum(fn($log) => $log->start_at->diffInSeconds($log->end_at));
        }

        return $ownSeconds + $childrenSeconds;
    }

    /**
     * Get human-readable total time (e.g. 2h 30m).
     */
    public function totalTrackedTimeHuman(): string
    {
        $seconds = $this->totalTrackedSeconds();
        if ($seconds === 0) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

}
