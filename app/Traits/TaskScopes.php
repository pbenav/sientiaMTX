<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait TaskScopes
{
    /**
     * Scope a query to only include tasks that are not ephemeral.
     */
    public function scopeNotEphemeral($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('metadata->is_ephemeral')
              ->orWhere('metadata->is_ephemeral', false)
              ->orWhere('metadata->is_ephemeral', 'false')
              ->orWhere('metadata->is_ephemeral', '0');
        });
    }

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
            ->whereNotIn('status->value', ['completed', 'cancelled']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
            ->where('status', '!=', 'completed');
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', '!=', 'completed');
    }

    public function scopeVisibleTo($query, $user, $isManager = false)
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
     * Scope for "What I should be working on or managing right now".
     * This handles the hierarchy to avoid showing both master and instance.
     */
    public function scopeOperationalFor($query, $user, Team $team, $includeFuture = false)
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
     * Specialized scope for focused views (Kanban/Matrix).
     * Filters for actionable items and applies deduplication for managers.
     */
    public function scopeFocusedFor($query, $user, Team $team, $includeFuture = false)
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
     * Specialized scope for the Kanban board.
     * Legacy wrapper for scopeFocusedFor.
     */
    public function scopeOperationalForKanban($query, $user, $team, $includeFuture = false)
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
