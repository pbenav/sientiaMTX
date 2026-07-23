<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Expediente: documento o carpeta de trabajo dentro de un equipo.
 *
 * Campos clave:
 * - code: código único generado automáticamente (EXP-YYYY-NNNN)
 * - visibility: 'public' o 'private'
 * - status: estado del expediente (activos, cerrados, archivados)
 * - metadata: array de campos personalizados
 */
class Expediente extends Model
{
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
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

    /**
     * Casting de atributos.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Equipo propietario del expediente.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Usuario creador del expediente.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Usuario responsable principal del expediente.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Asignaciones detalladas del expediente.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExpedienteAssignment::class);
    }

    /**
     * Usuarios asignados al expediente (colaboradores).
     *
     * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $assignedTo
     */
    public function assignedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'expediente_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * Grupos asignados al expediente.
     */
    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'expediente_assignments')
            ->withPivot('assigned_at', 'assigned_by_id')
            ->withTimestamps();
    }

    /**
     * Tareas vinculadas a este expediente.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Solo las tareas raíz (padres) vinculadas a este expediente.
     * Evita duplicación en la UI cuando las tareas tienen hijos.
     */
    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('parent_id');
    }

    /**
     * Actividades vinculadas a este expediente.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Solo las actividades raíz (padres) vinculadas a este expediente.
     */
    public function rootActivities(): HasMany
    {
        return $this->hasMany(Activity::class)->whereNull('parent_id');
    }

    /**
     * Adjuntos asociados a este expediente (polimórfico).
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }

    /**
     * Scope: filtra expedientes visibles para un usuario dado.
     *
     * Los coordinadores ven todo. Los miembros regulares ven solo los públicos
     * o los privados en los que están asignados o son creadores.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  User|null  $user  Usuario que consulta
     * @param  bool  $isCoordinator  Si el usuario es coordinador
     * @return \Illuminate\Database\Eloquent\Builder
     */
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

    /**
     * Expedientes relacionados entre sí (relación muchos a muchos auto-referencial).
     */
    public function relatedExpedientes(): BelongsToMany
    {
        return $this->belongsToMany(Expediente::class, 'expediente_related', 'expediente_id', 'related_id')->withTimestamps();
    }

    /**
     * Genera un código único para el expediente.
     *
     * Formato: EXP-YYYY-NNNN (ej: EXP-2026-0001)
     *
     * @return string Código único generado
     */
    public static function generateUniqueCode(): string
    {
        $year = date('Y');
        $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;

        do {
            $code = 'EXP-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Notas del expediente.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ExpedienteNote::class)->latest();
    }

    /**
     * Obtiene todos los usuarios que tienen acceso a este expediente.
     *
     * Para expedientes públicos, todos los miembros del equipo tienen acceso.
     * Para privados, se construye la lista de usuarios con acceso explícito
     * (creador, responsable, asignados, grupos, y miembros de tareas vinculadas).
     *
     * @return \Illuminate\Support\Collection<int, User>
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

