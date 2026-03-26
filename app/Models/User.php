<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'timezone',
        'theme',
        'layout',
        'is_admin',
        'google_id',
        'google_token',
        'google_refresh_token',
        'disk_quota',
        'disk_used',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // Relationships
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withTimestamps();
    }

    public function createdTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'created_by_id');
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by_id');
    }

    public function taskHistories(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Get pending invitations for this user based on email.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class, 'email', 'email');
    }

    /**
     * Get the user's preferred locale.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function hasAvailableQuota(int $bytes): bool
    {
        return ($this->disk_used + $bytes) <= $this->disk_quota;
    }

    /**
     * Get the user's role name in a specific team.
     */
    public function getRole(Team $team): ?string
    {
        $membership = $this->teams()->where('team_id', $team->id)->first();
        
        if (!$membership || !$membership->pivot->role_id) {
            return null;
        }

        $role = \DB::table('team_roles')->where('id', $membership->pivot->role_id)->first();
        return $role ? $role->name : null;
    }
}

