<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expediente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'created_by_id',
        'assigned_user_id',
        'code',
        'title',
        'description',
        'status',
        'priority',
        'visibility',
        'start_date',
        'end_date',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExpedienteAssignment::class);
    }

    public function assignedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'expediente_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'expediente_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get only the root tasks (parents) linked to this expediente.
     * This avoids duplication in the UI when tasks have children.
     */
    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('parent_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get only the root activities (parents) linked to this expediente.
     */
    public function rootActivities(): HasMany
    {
        return $this->hasMany(Activity::class)->whereNull('parent_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }

    public function scopeVisibleTo($query, $user, $isCoordinator = false)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $builder = $query instanceof \Illuminate\Database\Eloquent\Relations\Relation ? $query->getQuery() : $query;

        return $builder->where(function ($q) use ($user, $isCoordinator) {
            if ($isCoordinator) {
                // Coordinators see everything in the team
                return $q;
            } else {
                // Regular members see public expedientes or ones they are assigned to / created
                $q->where(function ($public) {
                    $public->where('visibility', 'public');
                })
                ->orWhere(function ($private) use ($user) {
                    $private->where('visibility', 'private')
                        ->where(function ($access) use ($user) {
                            $access->where('created_by_id', $user->id)
                                ->orWhere('assigned_user_id', $user->id)
                                ->orWhereHas('assignedTo', fn($sub) => $sub->where('users.id', $user->id))
                                ->orWhereHas('assignedGroups', fn($sub) => $sub->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                        });
                });
            }
        });
    }

    // --- Relational Engine ---
    
    /**
     * Linked dossiers (Expedientes Relacionados)
     */
    public function relatedExpedientes(): BelongsToMany
    {
        return $this->belongsToMany(Expediente::class, 'expediente_related', 'expediente_id', 'related_id')->withTimestamps();
    }
    
    /**
     * Automatic unique code generation (could be hooked in booted method later)
     */
    public static function generateUniqueCode(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'EXP-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ExpedienteNote::class)->latest();
    }

    /**
     * Obtiene todos los usuarios que tienen acceso a este expediente.
     */
    public function getUsersWithAccess()
    {
        $team = $this->team;
        if (!$team) {
            return collect();
        }

        // Si es público, todos los miembros del equipo tienen acceso por defecto.
        if ($this->visibility === 'public') {
            $members = $team->members()->orderBy('name')->get();
            foreach ($members as $member) {
                if ($this->created_by_id === $member->id) {
                    $member->access_reason = 'Creador';
                } elseif ($this->assigned_user_id === $member->id) {
                    $member->access_reason = 'Responsable';
                } elseif ($team->created_by_id === $member->id) {
                    $member->access_reason = 'Owner';
                } elseif ($team->isCoordinator($member)) {
                    $member->access_reason = 'Coordinador';
                } elseif ($member->is_admin) {
                    $member->access_reason = 'Admin';
                } else {
                    $member->access_reason = 'Miembro';
                }
            }
            return $members;
        }

        // Si es privado, construimos la lista de usuarios con acceso con sus motivos correspondientes.
        $usersMap = collect();

        $addUser = function ($user, $reason) use (&$usersMap) {
            if (!$user) return;
            if (!$usersMap->has($user->id)) {
                $user->access_reason = $reason;
                $usersMap->put($user->id, $user);
            }
        };

        // 1. Creador del expediente
        if ($this->creator) {
            $addUser($this->creator, 'Creador');
        }

        // 2. Responsable principal del expediente
        if ($this->assignedUser) {
            $addUser($this->assignedUser, 'Responsable');
        }

        // En modo estricto de privacidad (Deep Privacy), los Owners, Coordinadores y Admins NO tienen acceso
        // a expedientes privados a menos que estén explícitamente asignados. Por tanto, no se incluyen implícitamente aquí.

        // 6. Colaboradores asignados directamente al expediente
        foreach ($this->assignedTo as $assignedUser) {
            $addUser($assignedUser, 'Asignado');
        }

        // 7. Miembros de los grupos asignados directamente al expediente
        // Lo extraemos directamente de las relaciones ya anidadas
        foreach ($this->assignedGroups()->with('users')->get() as $group) {
            foreach ($group->users as $groupUser) {
                $addUser($groupUser, 'Grupo');
            }
        }

        // 8. Miembros asignados a tareas vinculadas a este expediente
        // Cargamos las tareas del expediente con todas sus relaciones para evitar leaks de ORM
        $tasks = $this->tasks()->with(['assignedUser', 'assignedTo', 'assignedGroups.users'])->get();
        
        foreach ($tasks as $task) {
            if ($task->assigned_user_id && $task->assignedUser) {
                $addUser($task->assignedUser, 'Tarea');
            }
            foreach ($task->assignedTo as $tAssigned) {
                $addUser($tAssigned, 'Tarea');
            }
            foreach ($task->assignedGroups as $taskGroup) {
                foreach ($taskGroup->users as $tgUser) {
                    $addUser($tgUser, 'Tarea');
                }
            }
        }

        return $usersMap->values()->sortBy('name');
    }
}

