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
        'is_autoprogrammable',
        'autoprogram_settings',
    ];
 
    protected $casts = [
        'metadata' => 'array',
        'due_date' => 'datetime',
        'scheduled_date' => 'datetime',
        'original_due_date' => 'datetime',
        'google_synced_at' => 'datetime',
        'is_archived' => 'boolean',
        'is_autoprogrammable' => 'boolean',
        'autoprogram_settings' => 'array',
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
            // 1. Any team member can see PUBLIC tasks
            $q->where('visibility', 'public')
            // 2. Owners (creators) can see their OWN tasks (public or private)
              ->orWhere('created_by_id', $user->id)
            // 3. Directly assigned users or collaborators can see tasks assigned to them
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
        $isModerator   = $team->isModerator($user);

        return $query->where(function ($main) use ($user, $isCoordinator, $isModerator) {
            if ($isCoordinator) {
                // COORDINATOR VIEW: Full Project Skeleton.
                $main->where(function ($incl) {
                    $incl->whereNull('parent_id')
                         ->orWhere('is_template', true)
                         ->orWhere(function ($hierarchical) {
                             $hierarchical->whereNotNull('parent_id')
                                          ->whereHas('parent', fn ($p) => $p->where('is_template', false));
                         });
                })
                ->whereNot(function ($excl) {
                    $excl->whereNotNull('parent_id')
                         ->whereHas('parent', fn ($p) => $p->where('is_template', true));
                });
            } elseif ($isModerator) {
                // MODERATOR (SUPERVISOR) VIEW: Skeleton + Own Instances.
                $main->where(function ($incl) use ($user) {
                    // 1. Project Skeleton (Templates & Root Tasks)
                    $incl->where('is_template', true)
                         ->orWhereNull('parent_id')
                         // 2. My own assigned instances
                         ->orWhere('assigned_user_id', $user->id)
                         ->orWhereHas('assignedTo', fn ($as) => $as->where('users.id', $user->id));
                })
                // PRIVACY: Hide instances of other users (children of templates not assigned to me).
                ->whereNot(function ($excl) use ($user) {
                    $excl->whereNotNull('parent_id')
                         ->whereHas('parent', fn ($p) => $p->where('is_template', true))
                         ->where('assigned_user_id', '!=', $user->id);
                });
            } else {
                // EXECUTION VIEW: My work.
                $main->where(function ($incl) use ($user) {
                    $incl->where('assigned_user_id', $user->id)
                         ->orWhereHas('assignedTo', fn ($as) => $as->where('users.id', $user->id))
                         ->orWhere('created_by_id', $user->id);
                })
                ->whereNot(function ($excl) use ($user) {
                    $excl->where('is_template', true)
                         ->whereHas('instances', function ($iq) use ($user) {
                             $iq->where('assigned_user_id', $user->id)
                                ->orWhereHas('assignedTo', fn ($q) => $q->where('users.id', $user->id));
                         });
                });
            }
        });
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
        $type = 'in_progress';
        if ($this->progress_percentage == 100 || $this->status === 'completed') {
            $type = 'done';
        } elseif ($this->progress_percentage == 0 && ($this->status === 'pending' || $this->status === 'todo')) {
            $type = 'todo';
        }

        $column = $this->team->kanbanColumns()
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
        if (!$this->is_autoprogrammable || empty($this->autoprogram_settings)) {
            return;
        }

        $settings = $this->autoprogram_settings;
        $frequency = $settings['frequency'] ?? 'daily';
        $interval = (int)($settings['interval'] ?? 1);
        $limitType = $settings['limit_type'] ?? 'count';
        $limitValue = $settings['limit_value'] ?? 1;
        $sequential = $settings['sequential'] ?? false;
        $skipWeekends = $settings['skip_weekends'] ?? false;

        $currentDate = $this->scheduled_date ? $this->scheduled_date->copy() : now();
        $baseDueDate = $this->due_date ? $this->due_date->copy() : $currentDate->copy()->addDay();
        $durationInSeconds = $currentDate->diffInSeconds($baseDueDate);

        $count = 0;
        $maxCount = $limitType === 'count' ? (int)$limitValue : 100; // Hard cap for safety
        $endDate = $limitType === 'date' ? Carbon::parse($limitValue) : null;

        $previousOccurrenceId = null;

        while (true) {
            $count++;
            if ($limitType === 'count' && $count > $maxCount) break;

            if ($count > 1) {
                switch ($frequency) {
                    case 'daily':
                        $currentDate = $currentDate->addDays($interval);
                        break;
                    case 'weekly':
                        $currentDate = $currentDate->addWeeks($interval);
                        break;
                    case 'monthly':
                        $currentDate = $currentDate->addMonths($interval);
                        break;
                    case 'yearly':
                        $currentDate = $currentDate->addYears($interval);
                        break;
                }
            }

            if ($skipWeekends && $currentDate->isWeekend()) {
                $currentDate = $currentDate->next(Carbon::MONDAY);
            }

            if ($endDate && $currentDate->greaterThan($endDate)) {
                break;
            }

            // Create the Occurrence Task
            $occurrence = $this->replicate(['uuid', 'google_task_id', 'google_synced_at']);
            $occurrence->parent_id = $this->id;
            $occurrence->is_autoprogrammable = false;
            $occurrence->autoprogram_settings = null;
            $occurrence->scheduled_date = $currentDate->copy();
            $occurrence->due_date = $currentDate->copy()->addSeconds($durationInSeconds);
            $occurrence->status = 'pending';
            $occurrence->progress_percentage = 0;
            
            // Handle Sequential Dependency
            if ($sequential && $previousOccurrenceId) {
                $occurrence->metadata = array_merge($occurrence->metadata ?? [], ['dependency_id' => $previousOccurrenceId]);
            }

            $occurrence->save();
            $previousOccurrenceId = $occurrence->id;

            // Trigger Instance Generation if this occurrence is a template
            if ($occurrence->is_template) {
                $this->spawnInstancesForOccurrence($occurrence);
            }
        }
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
}
