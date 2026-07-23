<?php

namespace App\Traits;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Trait ActivityScopes
 *
 * Define los scopes de consulta para el modelo Activity y sus subtipos.
 * Organiza las consultas por: tipos, vistas (Kanban/Matrix/Gantt),
 * visibilidad, y contexto operativo (gestión vs ejecución).
 *
 * @mixin \App\Models\Activity
 */
trait ActivityScopes
{
    // ─── Scopes básicos ───────────────────────────────────────────────────────

    /**
     * Filtra actividades por tipo exacto.
     */
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

    /**
     * Filtra actividades por un array de tipos.
     */
    public function scopeOfTypes(Builder $query, array $types)
    {
        return $query->whereIn('type', $types);
    }

    /**
     * Filtra actividades por equipo.
     */
    public function scopeByTeam(Builder $query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Filtra actividades activas (no archivadas).
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Filtra actividades archivadas.
     */
    public function scopeArchived(Builder $query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Filtra actividades vencidas (fecha de vencimiento pasada y sin completar/cancelar).
     */
    public function scopeOverdue(Builder $query)
    {
        return $query->where('due_date', '<', now())
            ->whereJsonDoesntContain('status->value', 'completed')
            ->whereJsonDoesntContain('status->value', 'cancelled');
    }

    /**
     * Filtra actividades con fecha de vencimiento hoy.
     */
    public function scopeDueToday(Builder $query)
    {
        return $query->whereDate('due_date', today());
    }

    // ─── Scopes por vista (Kanban, Matriz, Gantt) ─────────────────────────────

    /**
     * Filtra actividades para mostrar en vista Kanban según los tipos definidos.
     */
    public function scopeForKanban(Builder $query)
    {
        return $query->whereIn('type', $this->getScopesKanbanTypes());
    }

    /**
     * Filtra actividades para mostrar en vista Matriz de Eisenhower.
     */
    public function scopeForMatrix(Builder $query)
    {
        return $query->whereIn('type', $this->getScopesMatrixTypes())
                      ->where('is_archived', false);
    }

    /**
     * Filtra actividades para mostrar en vista Gantt (con fecha y no archivadas).
     */
    public function scopeForGantt(Builder $query)
    {
        return $query->whereIn('type', $this->getScopesGanttTypes())
                      ->whereNotNull('due_date')
                      ->where('is_archived', false);
    }

    // ─── Scopes de visibilidad ────────────────────────────────────────────────

    /**
     * Filtra actividades visibles para un usuario según su rol y la visibilidad de cada actividad.
     *
     * Para managers: incluye públicas, plantillas públicas, creadas por el usuario,
     * y semi-privadas asignadas al usuario.
     * Para miembros normales: incluye públicas sin asignar, creadas por el usuario,
     * semi-privadas asignadas, asignadas directamente, o asignadas a grupos del usuario.
     */
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

    /**
     * Filtra actividades no efímeras (don metadata->is_ephemeral es null o false).
     */
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

    /**
     * Filtra actividades enfocadas para un usuario en un equipo.
     *
     * Para managers: incluye plantillas maestras, tareas raíz creadas por el manager,
     * e instancias asignadas al manager.
     * Para miembros normales: solo tareas hoja (sin hijos) asignadas al usuario,
     * creadas por el usuario, públicas sin asignar, o mapeadas a través de task_assignments.
     */
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

    /**
     * Filtra actividades operativas para un usuario en un equipo.
     *
     * Para managers: muestra el esqueleto (plantillas y raíces) con deduplicación
     * para priorizar el Plan Maestro.
     * Para miembros: muestra trabajo asignado y tareas puras sin asignar,
     * con deduplicación para ocultar padres si se ven hijas asignadas.
     */
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

    /**
     * Filtra actividades para vista operativa Kanban (hojas no plantillas, enfocadas para el usuario).
     */
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

    /**
     * Obtiene los tipos de actividad válidos para vista Kanban.
     */
    protected function getScopesKanbanTypes(): array
    {
        $const = \App\Models\Activity::class . '::KANBAN_TYPES';
        return constant($const);
    }

    /**
     * Obtiene los tipos de actividad válidos para vista Matriz de Eisenhower.
     */
    protected function getScopesMatrixTypes(): array
    {
        $const = \App\Models\Activity::class . '::MATRIX_TYPES';
        return constant($const);
    }

    /**
     * Obtiene los tipos de actividad válidos para vista Gantt.
     */
    protected function getScopesGanttTypes(): array
    {
        $const = \App\Models\Activity::class . '::GANTT_TYPES';
        return constant($const);
    }
}
