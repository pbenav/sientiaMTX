<?php

namespace App\Traits;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

trait ActivityScopes
{
    // ─── Scopes básicos ───────────────────────────────────────────────────────
    public function scopeOfType(Builder $query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Filtra actividades por relevancia según su tipo y estado.
     * Excluye estados obsoletos (deprecated, completed, cancelled, under_review).
     * Las plantillas maestras se excluyen del filtro de estado.
     */
    public function scopeRelevant(Builder $query)
    {
        return $query->where(function ($q) {
            // Plantillas maestras: siempre relevantes (independientemente del estado)
            $q->where('is_template', true)
            // No plantillas: filtrar por estado según tipo
            ->orWhere(function ($sub) {
                // task: solo pending + in_progress
                $sub->where(function ($inner) {
                    $inner->where('type', 'task')
                          ->whereIn('status->value', ['pending', 'in_progress']);
                })
                // agreement: solo proposed
                ->orWhere(function ($inner) {
                    $inner->where('type', 'agreement')
                          ->whereIn('status->value', ['proposed']);
                })
                // meeting: solo scheduled
                ->orWhere(function ($inner) {
                    $inner->where('type', 'meeting')
                          ->whereIn('status->value', ['scheduled']);
                })
                // document: draft, editing, reviewed, uploaded (NO under_review)
                ->orWhere(function ($inner) {
                    $inner->where('type', 'document')
                          ->whereIn('status->value', ['draft', 'editing', 'reviewed', 'uploaded']);
                })
                // reminder: solo pending
                ->orWhere(function ($inner) {
                    $inner->where('type', 'reminder')
                          ->whereIn('status->value', ['pending']);
                })
                // link: solo active
                ->orWhere(function ($inner) {
                    $inner->where('type', 'link')
                          ->whereIn('status->value', ['active']);
                })
                // sin estado definido (NULL): considerar relevante
                ->orWhereNull('status->value');
            });
        });
    }

    public function scopeOfTypes(Builder $query, array $types)
    {
        return $query->whereIn('type', $types);
    }

    public function scopeByTeam(Builder $query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived(Builder $query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeOverdue(Builder $query)
    {
        return $query->where('due_date', '<', now())
            ->whereJsonDoesntContain('status->value', 'completed')
            ->whereJsonDoesntContain('status->value', 'cancelled');
    }

    public function scopeDueToday(Builder $query)
    {
        return $query->whereDate('due_date', today());
    }

    // ─── Scopes por vista (Kanban, Matriz, Gantt) ─────────────────────────────
    public function scopeForKanban(Builder $query)
    {
        return $query->whereIn('type', $this->getScopesKanbanTypes());
    }

    public function scopeForMatrix(Builder $query)
    {
        return $query->whereIn('type', $this->getScopesMatrixTypes())
                     ->where('is_archived', false);
    }

    public function scopeForGantt(Builder $query)
    {
        return $query->whereIn('type', $this->getScopesGanttTypes())
                     ->whereNotNull('due_date')
                     ->where('is_archived', false);
    }

    // ─── Scopes de visibilidad ────────────────────────────────────────────────
    public function scopeVisibleTo(Builder $query, User $user, bool $isManager = false)
    {
        if (!$user) return $query->whereRaw('1 = 0');

        $builder = $query instanceof Relation ? $query->getQuery() : $query;

        return $builder->where(function ($q) use ($user, $isManager) {
            // Política de privacidad:
            // - 'public': lo ve todo el equipo
            // - 'semi-private' o 'semiprivate': creador + asignados
            // - 'private' o NULL: solo creador (nada más)
            if ($isManager) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($template) {
                      $template->where('is_template', true)
                               ->where('visibility', 'public');
                  })
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere(function ($semi) use ($user) {
                      $semi->whereIn('visibility', ['semi-private', 'semiprivate'])
                           ->where(function ($assigned) use ($user) {
                               $assigned->whereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                                        ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                           });
                  });
            } else {
                // Miembros: solo ven público sin asignados, o si son creador/assignado
                $q->where(function ($unassigned) {
                    $unassigned->where('visibility', 'public')
                               ->whereDoesntHave('assignments')
                               ->whereNotExists(function ($sub) {
                                   $sub->select(DB::raw(1))
                                       ->from('activity_task_mapping')
                                       ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                       ->whereColumn('activity_task_mapping.activity_id', 'activities.id');
                               });
                })
                ->orWhere('created_by_id', $user->id)
                ->orWhere(function ($semi) use ($user) {
                    $semi->whereIn('visibility', ['semi-private', 'semiprivate'])
                         ->where(function ($assigned) use ($user) {
                             $assigned->whereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                                      ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                         });
                })
                ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            }
        });
    }

    public function scopeNotEphemeral(Builder $query)
    {
        return $query->where(function ($q) {
            $q->whereNull('metadata->is_ephemeral')
              ->orWhere('metadata->is_ephemeral', false)
              ->orWhere('metadata->is_ephemeral', 'false')
              ->orWhere('metadata->is_ephemeral', '0');
        });
    }

    // ─── Scopes de contexto operativo ─────────────────────────────────────────
    public function scopeFocusedFor(Builder $query, $user, Team $team, $includeFuture = false)
    {
        $userId = $user->id;
        $isManager = $team->isManager($user);

        if ($isManager) {
            // Coordinadores/Managers: ven plantillas, raíces (con o sin hijos) e instancias asignadas
            $query->where(function ($q) use ($userId) {
                // 1. Plantillas maestras
                $q->where('is_template', true)
                  // 2. Tareas raíz creadas por el manager (independientemente de si tienen hijos)
                  ->orWhere(function($roots) use ($userId) {
                      $roots->whereNull('parent_id')
                            ->where('created_by_id', $userId);
                  });
            })
            ->orWhere(function ($q) use ($userId) {
                // 3. Instancias/hojas asignadas al manager (ejecución directa)
                $q->whereDoesntHave('children')
                  ->where(function ($inner) use ($userId) {
                      $inner->whereHas('assignedTo', fn($sq) => $sq->where('users.id', $userId))
                            ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                            ->orWhereExists(function ($sub) use ($userId) {
                                $sub->select(DB::raw(1))
                                    ->from('activity_task_mapping')
                                    ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                    ->whereColumn('activity_task_mapping.activity_id', 'activities.id')
                                    ->where(function ($a) use ($userId) {
                                        $a->where('task_assignments.user_id', $userId)
                                          ->orWhereExists(function ($g) use ($userId) {
                                              $g->select(DB::raw(1))
                                                ->from('group_user')
                                                ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                                                ->where('group_user.user_id', $userId);
                                          });
                                    });
                            });
                  });
            });
        } else {
            // Miembros normales: solo tareas hoja (sin hijos), no plantillas
            $query->where('is_template', false)
                ->whereDoesntHave('children')
                ->where(function ($q) use ($userId) {
                    $q->whereHas('assignedTo', fn($sq) => $sq->where('users.id', $userId))
                      ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                      ->orWhere(function($roots) use ($userId) {
                          $roots->whereNull('parent_id')
                                ->where('created_by_id', $userId);
                      })
                      ->orWhere(function ($unassigned) {
                          $unassigned->where('visibility', 'public')
                                     ->whereDoesntHave('assignedTo')
                                     ->whereDoesntHave('assignedGroups')
                                     ->whereNotExists(function ($sub) {
                                         $sub->select(DB::raw(1))
                                             ->from('activity_task_mapping')
                                             ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                                             ->whereColumn('activity_task_mapping.activity_id', 'activities.id');
                                     });
                      })
                      ->orWhereExists(function ($sub) use ($userId) {
                          $sub->select(DB::raw(1))
                              ->from('activity_task_mapping')
                              ->join('task_assignments', 'activity_task_mapping.task_id', '=', 'task_assignments.task_id')
                              ->whereColumn('activity_task_mapping.activity_id', 'activities.id')
                              ->where(function ($a) use ($userId) {
                                  $a->where('task_assignments.user_id', $userId)
                                    ->orWhereExists(function ($g) use ($userId) {
                                        $g->select(DB::raw(1))
                                          ->from('group_user')
                                          ->whereColumn('group_user.group_id', 'task_assignments.group_id')
                                          ->where('group_user.user_id', $userId);
                                    });
                              });
                      });
                });
        }

        if (!$includeFuture) {
            $query->where(function ($q) {
                $q->whereNull('scheduled_date')
                  ->orWhere('scheduled_date', '<=', now())
                  ->orWhere('metadata->is_occurrence', true);
            });
        }

        return $query;
    }

    public function scopeOperationalFor(Builder $query, $user, Team $team, $includeFuture = false)
    {
        $isManager = $team->isManager($user);
        $userId = $user->id;

        $query->where(function ($main) use ($userId, $isManager) {
            if ($isManager) {
                // GESTIÓN (Managers/Coordinators): Ve el esqueleto (Plantillas y Raíces)
                $main->where(function($q) {
                    $q->whereNull('parent_id')
                      ->orWhere('is_template', true);
                });

                // DEDUPLICACIÓN EN GESTIÓN: Si el manager tiene una instancia propia, 
                // priorizamos ver el Plan Maestro (donde puede gestionar todo) y evitamos 
                // ver la instancia suelta arriba para no triplicar.
                $main->where(function($q) use ($userId) {
                    $q->where('is_template', true)
                      ->orWhereDoesntHave('assignedTo')
                      ->orWhereHas('assignedTo', fn($at) => $at->where('users.id', '!=', $userId))
                      ->orWhereNull('parent_id'); // SIEMPRE ver tareas raíz
                });
            } else {
                // MIEMBRO (Contexto Ejecución): Ve su trabajo asignado Y las tareas puras (sin asignar a nadie)
                $main->where(function ($q) use ($userId) {
                    $q->whereHas('assignedTo', fn ($as) => $as->where('users.id', $userId))
                      ->orWhereHas('assignedGroups', fn ($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                      ->orWhere(function ($own) use ($userId) {
                          $own->where('created_by_id', $userId)
                              ->whereNull('parent_id');
                      })
                      ->orWhere(function ($unassigned) {
                          $unassigned->where('is_template', false)
                                     ->whereDoesntHave('assignedTo')
                                     ->whereDoesntHave('assignedGroups')
                                     ->where('visibility', 'public');
                      });
                });

                // DEDUPLICACIÓN EN EJECUCIÓN (Miembro): Si ve la hija, ocultamos el padre
                $main->whereDoesntHave('children', function ($q) use ($userId) {
                    $q->where(function($sub) use ($userId) {
                        $sub->whereHas('assignedTo', fn($at) => $at->where('users.id', $userId))
                            ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)));
                    });
                });
            }
        });

        if (!$includeFuture) {
            $query->where(function ($q) {
                $q->whereNull('scheduled_date')
                  ->orWhere('scheduled_date', '<=', now())
                  ->orWhere('metadata->is_occurrence', true);
            });
        }

        return $query;
    }

    public function scopeOperationalForKanban(Builder $query, $user, $team, $includeFuture = false)
    {
        return $query->where(function($q) {
                $q->whereDoesntHave('children')
                  ->where('is_template', false);
            })
            ->where(function($q) use ($user, $team, $includeFuture) {
                 $q->focusedFor($user, $team, $includeFuture);
            });
    }

    // ─── Helpers para scopes ─────────────────────────────────────────────────
    protected function getScopesKanbanTypes(): array
    {
        $const = \App\Models\Activity::class . '::KANBAN_TYPES';
        return constant($const);
    }

    protected function getScopesMatrixTypes(): array
    {
        $const = \App\Models\Activity::class . '::MATRIX_TYPES';
        return constant($const);
    }

    protected function getScopesGanttTypes(): array
    {
        $const = \App\Models\Activity::class . '::GANTT_TYPES';
        return constant($const);
    }
}
