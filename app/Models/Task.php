<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    ];

    protected $casts = [
        'metadata' => 'array',
        'due_date' => 'datetime',
        'scheduled_date' => 'datetime',
        'original_due_date' => 'datetime',
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

            $completedCount = $this->children()->where('status', 'completed')->count();
            return (int) (($completedCount / $totalCount) * 100);
        }

        // For individual tasks, return the manual progress percentage
        // If status is completed, it should be 100 anyway, but we return the column value
        return (int) ($this->attributes['progress_percentage'] ?? ($this->status === 'completed' ? 100 : 0));
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

    public function scopeVisibleTo($query, $user)
    {
        return $query->where(function($q) use ($user) {
            $q->where('visibility', 'public')
              ->orWhere('created_by_id', $user->id)
              ->orWhere('assigned_user_id', $user->id)
              ->orWhereHas('assignedTo', function($subq) use ($user) {
                  $subq->where('users.id', $user->id);
              })
              ->orWhereNull('visibility');
        });
    }

    public function scopeOperationalFor($query, $user, Team $team)
    {
        $isCoordinator = $team->isCoordinator($user);

        return $query->where(function($q) use ($user, $isCoordinator) {
            if ($isCoordinator) {
                // MANAGEMENT VIEW:
                // 1. All templates (the source of truth for assignments)
                // 2. All root tasks (not templates, not instances)
                // 3. Child tasks of non-templates (standard subtasks)
                // 4. Tasks explicitly assigned to them OR created by them (as long as they aren't instances of their own templates)
                $q->where('is_template', true)
                  ->orWhere(function($subq) {
                      $subq->whereNull('parent_id')
                           ->where('is_template', false);
                  })
                  ->orWhere(function($subq) {
                      $subq->whereNotNull('parent_id')
                           ->whereHas('parent', function($pq) {
                               $pq->where('is_template', false);
                           });
                  })
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere('assigned_user_id', $user->id);
                
                // CRITICAL: Filter out instances if the user is the coordinator/creator of the parent
                // (They should manage the Template, not see a duplicate Instance)
                $q->whereNot(function($subq) use ($user) {
                    $subq->whereNotNull('parent_id')
                         ->whereHas('parent', function($pq) use ($user) {
                             $pq->where('is_template', true)
                                ->where('created_by_id', $user->id);
                         });
                });
            } else {
                // EXECUTION VIEW: 
                // 1. Tasks explicitly assigned to them (Instances or direct assignments)
                // 2. Tasks they created
                // 3. Unassigned root tasks available for team pick-up
                $q->where('assigned_user_id', $user->id)
                  ->orWhereHas('assignedTo', function($subq) use ($user) {
                      $subq->where('users.id', $user->id);
                  })
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere(function($subq) {
                      $subq->whereNull('assigned_user_id')
                           ->whereDoesntHave('assignedTo')
                           ->whereNull('parent_id')
                           ->where('is_template', false);
                  });
            }
        });
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', '!=', 'completed');
    }
}

