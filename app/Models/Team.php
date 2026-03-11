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

    protected $fillable = ['name', 'slug', 'description', 'created_by_id'];

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
     * Check if a user is a manager for this team (Coordinator or Moderator)
     */
    public function isManager(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivotIn('role_id', function ($query) {
                $query->select('id')->from('team_roles')
                    ->whereIn('name', ['coordinator', 'moderator']);
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
}
