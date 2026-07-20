<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Str;

use App\Traits\HasUuid;
use App\Traits\HandlesEisenhowerMatrix;
use App\Traits\TaskScopes;
use App\Traits\TaskTracking;
use App\Traits\TaskVisibility;
use App\Traits\TaskOperations;
use App\Traits\TaskNotifications;
use App\Traits\TaskConversion;

class Task extends Model
{
    use HasFactory, SoftDeletes, HandlesEisenhowerMatrix, HasUuid, TaskScopes, TaskTracking, TaskVisibility, TaskOperations, TaskNotifications, TaskConversion;

    protected static function boot(): void
    {
        parent::boot();

        // Alerta de depuración para rastrear accesos al modelo legacy Task
        static::retrieved(function ($task) {
            try {
                if (request()) {
                    $url = request()->fullUrl();
                    $action = request()->route()?->getActionName() ?? 'N/A';
                    \Illuminate\Support\Facades\Log::warning("LEGACY TASK ACCESS: Se ha accedido al modelo obsoleto Task (ID: {$task->id}). URL: {$url} | Action: {$action}");
                } else {
                    \Illuminate\Support\Facades\Log::warning("LEGACY TASK ACCESS: Se ha accedido al modelo obsoleto Task (ID: {$task->id}) desde CLI / Background Job.");
                }
            } catch (\Exception $e) {
                // Silenciar para no interferir con la ejecución normal
            }
        });
    }


    protected $fillable = [
        'team_id',
        'title',
        'description',
        'priority',
        'auto_priority',
        'urgency',
        'status',
        'scheduled_date',
        'due_date',
        'original_due_date',
        'created_by_id',
        'metadata',
        'observations',
        'parent_id',
        'expediente_id',
        'is_template',
        'assigned_user_id',
        'progress_percentage',
        'visibility',
        'google_task_id',
        'google_task_list_id',
        'google_calendar_event_id',
        'google_calendar_id',
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
        'service_id',
        'is_autoprogrammable',
        'is_timeline_locked',
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
        'service_id' => 'integer',
        'is_autoprogrammable' => 'boolean',
        'auto_priority' => 'boolean',
        'is_timeline_locked' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

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
            ->withTimestamps()
            ->orderBy('name');
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
        // La tabla pivot fue renombrada de skill_task -> activity_skills en la migración
        // 2026_07_06_172548_rename_skill_task_to_activity_skills, con FK activity_id.
        // El modelo Task mantiene compatibilidad pasiva; las skills se gestionan desde Activity.
        return $this->belongsToMany(Skill::class, 'activity_skills', 'activity_id', 'skill_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    // Relationship: A task can have a parent task (dependency)
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    // Relationship: A task can have an associated appointment
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'task_id');
    }

    // Relationship: A task can have many subtasks or dependent tasks
    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')
            ->orderBy('title')
            ->orderByRaw('(SELECT name FROM users WHERE users.id = tasks.assigned_user_id)');
    }

    // Relationship: A template task has many instances
    public function instances(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->where('is_template', false)->orderBy('title');
    }

    // Relationship: An instance task belongs to a user
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the associated forum thread.
     */
    public function forumThread(): HasOne
    {
        return $this->hasOne(ForumThread::class);
    }

    public function privateNotes()
    {
        return $this->hasMany(TaskPrivateNote::class);
    }

    /**
     * Get the private note for the current user.
     */
    public function currentPrivateNote()
    {
        return $this->hasOne(TaskPrivateNote::class)->where('user_id', auth()->id());
    }

    // Time Tracking Relationships
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }

    public function kanbanColumn(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class);
    }

    /**
     * Get the ratings for the task.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(TaskRating::class);
    }
}
