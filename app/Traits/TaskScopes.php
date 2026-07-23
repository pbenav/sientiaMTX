<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait TaskScopes
 *
 * Define los scopes de consulta para el modelo Task y sus subtipos.
 * Organiza las consultas por: estado, prioridad, visibilidad, fechas,
 * y contexto operativo (gestión vs ejecución).
 *
 * @mixin \App\Models\Task
 */
trait TaskScopes
{
    /**
     * Filtra tareas no efímeras (donde metadata->is_ephemeral es null o false).
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

    /**
     * Filtra tareas por equipo.
     */
    public function scopeByTeam(Builder $query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Filtra tareas por estado.
     */
    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filtra tareas por prioridad.
     */
    public function scopeByPriority(Builder $query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Filtra tareas vencidas (fecha de vencimiento pasada y sin completar/cancelar).
     */
    public function scopeOverdue(Builder $query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status->value', ['completed', 'cancelled']);
    }

    /**
     * Filtra tareas con vencimiento hoy y estado no completado.
     */
    public function scopeDueToday(Builder $query)
    {
        return $query->whereDate('due_date', today())
            ->where('status', '!=', 'completed');
    }

    /**
     * Filtra tareas con vencimiento esta semana y estado no completado.
     */
    public function scopeDueThisWeek(Builder $query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', '!=', 'completed');
    }

    /**
     * Filtra tareas visibles para un usuario según su rol y la visibilidad de cada tarea.
     *
     * Para managers: incluye todas las tareas excepto las estrictamente privadas de otros usuarios.
     * Para miembros: incluye tareas públicas sin asignar, creadas por el usuario, asignadas al usuario,
     * o asignadas a grupos del usuario.
     */
    public function scopeVisibleTo(Builder $query, $user, $isManager = false)
    {
        // Safety check: If no user is provided, the task is invisible by default.
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Ensure we are working with a builder to avoid cloning issues on Relation objects.
        $builder = $query instanceof \Illuminate\Database\Eloquent\Relations\Relation ? $query->getQuery() : $query;

        return $builder->where(function ($q) use ($user, $isManager) {
            // Política de privacidad:
            // - 'public': lo ve todo el equipo
            // - 'semi-private' o 'semiprivate': creador + asignados
            // - 'private' o NULL: solo creador (nada más)
            if ($isManager) {
                // Managers/coordinators can see ALL tasks except those strictly private to other users
                $q->where(function ($mQuery) use ($user) {
                    $mQuery->whereNotIn('visibility', ['private'])
                           ->orWhereNull('visibility')
                           ->orWhere('created_by_id', $user->id)
                           ->orWhere('assigned_user_id', $user->id)
                           ->orWhereHas('assignedTo', fn($s) => $s->where('users.id', $user->id))
                           ->orWhereHas('assignedGroups', fn($s) => $s->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                });
            } else {
                // Miembros: ven público sin asignados, o si son creador/asignado
                $q->where(function ($unassigned) {
                    $unassigned->whereNull('assigned_user_id')
                               ->whereDoesntHave('assignedTo')
                               ->whereDoesntHave('assignedGroups')
                               ->whereNotIn('visibility', ['private', 'semi-private', 'semiprivate']);
                })
                ->orWhere('created_by_id', $user->id)
                ->orWhere('assigned_user_id', $user->id)
                ->orWhereHas('assignedTo', fn($sub) => $sub->where('users.id', $user->id))
                ->orWhereHas('assignedGroups', fn($sub) => $sub->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            }
        });
    }

    /**
     * Filtra tareas operativas para un usuario en un equipo.
     *
     * Para managers: prioriza el Plan Maestro y evita duplicación con instancias propias.
     * Para miembros: muestra trabajo asignado y tareas puras sin asignar,
     * con deduplicación para ocultar padres si se ven hijas asignadas.
     */
    public function scopeOperationalFor(Builder $query, $user, Team $team, $includeFuture = false)
    {
        $isManager = $team->isManager($user);

        $query->where(function ($main) use ($user, $isManager) {
            if ($isManager) {
                // DEDUPLICACIÓN EN GESTIÓN: Si el manager tiene una instancia propia,
                // priorizamos ver el Plan Maestro (donde puede gestionar todo) y evitamos
                // ver la instancia suelta arriba para no triplicar.
                $main->where(function($q) use ($user) {
                    $q->where('is_template', true)
                      ->orWhereNull('assigned_user_id')
                      ->orWhere('assigned_user_id', '!=', $user->id)
                      ->orWhereNull('parent_id'); // SIEMPRE ver tareas raíz
                });
            } else {
                // MIEMBRO (Contexto Ejecución): Ve su trabajo asignado Y las tareas puras (sin asignar a nadie)
                // Permitimos ver plantillas y tareas autoprogramables si el usuario es creador o está asignado explícitamente.
                $main->where(function ($q) use ($user) {
                    $q->where('assigned_user_id', $user->id)
                      ->orWhereHas('assignedTo', fn ($as) => $as->where('users.id', $user->id))
                      ->orWhereHas('assignedGroups', fn ($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)))
                      ->orWhere(function ($own) use ($user) {
                          $own->where('created_by_id', $user->id)
                              ->whereNull('parent_id');
                      })
                      ->orWhere(function ($unassigned) {
                          $unassigned->where('is_template', false)
                                     ->whereNull('assigned_user_id')
                                     ->whereDoesntHave('assignedTo')
                                     ->whereDoesntHave('assignedGroups')
                                     ->where('visibility', 'public');
                      });
                });

                // DEDUPLICACIÓN EN EJECUCIÓN (Miembro): Si ve la hija, ocultamos el padre
                // Reforzamos para que compruebe tanto asignación directa como pivot en los hijos
                $main->whereDoesntHave('children', function ($q) use ($user) {
                    $q->where(function($sub) use ($user) {
                        $sub->where('assigned_user_id', $user->id)
                            ->orWhereHas('assignedTo', fn($at) => $at->where('users.id', $user->id))
                            ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
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
     * Filtra tareas enfocadas para vistas especializadas (Kanban/Matriz).
     * Solo muestra tareas finales (hojas), no plantillas maestras ni contenedores con hijos.
     * Enfoque siempre en ejecución: tareas asignadas, raíces creadas por el usuario, o públicas sin asignar.
     */
    public function scopeFocusedFor(Builder $query, $user, Team $team, $includeFuture = false)
    {
        $userId = $user->id;

        $query->where('is_template', false) // NUNCA mostrar planes maestros en Vistas Enfocadas (Matrix/Kanban)
            ->whereDoesntHave('children') // ELIMINAR FANTASMAS: Solo mostrar tareas finales (hojas), no contenedores u ocurrencias con hijos
            ->where(function ($q) use ($userId) {
                // ENFOQUE SIEMPRE EN EJECUCIÓN: Ver lo que tengo asignado, raíces creadas por mí, o tareas sin asignar (públicas)
                $q->where('assigned_user_id', $userId)
                  ->orWhereHas('assignedTo', fn($sq) => $sq->where('users.id', $userId))
                  ->orWhereHas('assignedGroups', fn($ag) => $ag->whereHas('users', fn($u) => $u->where('users.id', $userId)))
                  ->orWhere(function($roots) use ($userId) {
                      $roots->whereNull('parent_id')
                            ->where('created_by_id', $userId);
                  })
                  ->orWhere(function ($unassigned) {
                      $unassigned->whereNull('assigned_user_id')
                                 ->whereDoesntHave('assignedTo')
                                 ->whereDoesntHave('assignedGroups')
                                 ->where('visibility', 'public');
                  });
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
     * Scope especializado para el tablero Kanban.
     * Wrapper legacy para scopeFocusedFor. Solo tareas finales (sin hijos) y no plantillas maestras.
     */
    public function scopeOperationalForKanban(Builder $query, $user, $team, $includeFuture = false)
    {
        // EL KANBAN ES SAGRADO: Solo tareas finales (sin hijos) y que no sean plantillas maestras.
        // Aplicamos un filtro de "túnel" para ignorar cualquier otro orWhere de scopes anteriores.
        return $query->where(function($q) {
                $q->whereDoesntHave('children')
                  ->where('is_template', false);
            })
            ->where(function($q) use ($user, $team, $includeFuture) {
                 $q->focusedFor($user, $team, $includeFuture);
            });
    }
}
