<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'created_by_id', 'quadrant_colors'];

    protected $casts = [
        'quadrant_colors' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationship: A team has many tasks
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Relationship: A team has many users (members)
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role_id');
    }

    // Relationship: A team has many groups
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    // Relationship: A team has many calendar events
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function kanbanColumns(): HasMany
    {
        return $this->hasMany(KanbanColumn::class)->orderBy('order_index');
    }

    // Get creator of the team
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Check if a user is a coordinator for this team (Admin)
     */
    public function isCoordinator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role_id', function ($query) {
                $query->select('id')->from('team_roles')->where('name', 'coordinator');
            })
            ->exists();
    }

    /**
     * Check if a user is a manager for this team (Coordinator)
     */
    public function isManager(User $user): bool
    {
        return $this->isCoordinator($user) || $this->isModerator($user);
    }

    /**
     * Check if a user is a moderator for this team
     */
    public function isModerator(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role_id', function ($query) {
                $query->select('id')->from('team_roles')
                    ->where('name', 'moderator');
            })
            ->exists();
    }

    /**
     * Check if a user is the owner of the team
     */
    public function isOwner(User $user): bool
    {
        return $this->created_by_id === $user->id;
    }
    /**
     * Get the quadrant color configuration for this team.
     */
    public function getQuadrantConfig(): array
    {
        $defaults = [
            1 => ['color' => '#ef4444', 'bg' => 'bg-red-200 border-red-400 dark:bg-red-500/25 dark:border-red-500/60', 'dot' => 'bg-red-500'],
            2 => ['color' => '#3b82f6', 'bg' => 'bg-blue-200 border-blue-400 dark:bg-blue-500/25 dark:border-blue-500/60', 'dot' => 'bg-blue-500'],
            3 => ['color' => '#f59e0b', 'bg' => 'bg-amber-200 border-amber-400 dark:bg-amber-500/25 dark:border-amber-500/60', 'dot' => 'bg-amber-500'],
            4 => ['color' => '#6b7280', 'bg' => 'bg-gray-200 border-gray-400 dark:bg-gray-500/25 dark:border-gray-500/60', 'dot' => 'bg-gray-500'],
        ];

        if (empty($this->quadrant_colors)) {
            return $defaults;
        }

        $config = $defaults;
        foreach ($this->quadrant_colors as $q => $color) {
            if (isset($config[$q])) {
                $config[$q]['color'] = $color;
            }
        }

        return $config;
    }
}
