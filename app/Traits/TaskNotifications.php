<?php

namespace App\Traits;

/**
 * Trait TaskNotifications
 *
 * Maneja las notificaciones automáticas para eventos de tareas:
 * - notifyCreatorAndCoordinators: notifica al creador y coordinadores sobre un evento
 * - notifyCoordinatorsIfCompleted: notifica a coordinadores cuando una tarea se completa
 *   según las reglas de privacidad (private, semi-private, public) y tipo de tarea.
 *
 * @mixin \App\Models\Task
 */
trait TaskNotifications
{
    /**
     * Notifica al creador y coordinadores sobre un evento de tarea.
     *
     * Recipients:
     * 1. Creador (si no es el usuario autenticado)
     * 2. Coordinadores filtrados por visibilidad:
     *    - 'public': todos los coordinadores
     *    - No pública: solo coordinadores involucrados (creador, asignado, o en grupos)
     *
     * @param \Illuminate\Notifications\Notification $notification Instancia de notificación a enviar
     */
    public function notifyCreatorAndCoordinators($notification)
    {
        $recipients = collect();

        // 1. Add Creator
        if ($this->creator && $this->creator->id !== auth()->id()) {
            $recipients->push($this->creator);
        }

        // 2. Add Coordinators (filtered by visibility/involvement)
        $coordinators = $this->team->coordinators()
            ->where('users.id', '!=', auth()->id())
            ->get();
            
        $filteredCoordinators = $coordinators->filter(function ($coordinator) {
            if ($this->visibility === 'public') {
                return true;
            }
            // For non-public activities, check if coordinator is involved
            if ($this->created_by_id === $coordinator->id) return true;
            if ($this->assigned_user_id === $coordinator->id) return true;
            if ($this->assignedTo->contains('id', $coordinator->id)) return true;
            if ($this->assignedGroups->filter(fn($g) => $g->users->contains('id', $coordinator->id))->isNotEmpty()) return true;
            
            return false;
        });
        
        $recipients = $recipients->merge($filteredCoordinators)->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    /**
     * Notifica a los coordinadores si la tarea fue completada y cumple criterios específicos.
     *
     * Reglas de notificación al completar:
     * - 'private' o NULL: solo al creador (si no es quien completó)
     * - 'semi-private': creador + asignado (si no son quien completó)
     * - 'public' + template/coordinator-created: a todos los coordinadores (excluyendo al actor)
     * - 'public' normal: solo al creador (si no es quien completó)
     *
     * Envía TaskCompletedNotification a cada destinatario.
     */
    public function notifyCoordinatorsIfCompleted()
    {
        if ($this->status !== 'completed') {
            return;
        }

        $actor = auth()->user() ?? $this->assignedUser ?? $this->creator;
        $actorId = $actor ? $actor->id : null;
        
        $recipients = collect();

        // 1. TAREAS PRIVADAS ('private' o NULL)
        // Solo creador
        if ($this->visibility === 'private' || is_null($this->visibility)) {
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
        } 
        // 2. TAREAS SEMIPRIVADAS ('semi-private' o 'semiprivate')
        // Creador + asignados
        elseif (in_array($this->visibility, ['semi-private', 'semiprivate'])) {
            if ($this->creator && $this->creator->id !== $actorId) {
                $recipients->push($this->creator);
            }
            if ($this->assignedUser && $this->assignedUser->id !== $actorId) {
                $recipients->push($this->assignedUser);
            }
        } 
        // 3. TAREAS PÚBLICAS ('public')
        // Si es Plan Maestro o supervisada por coordinador, se avisa a coordinadores. Si no, solo al creador si la completó otro.
        else {
            if ($this->is_template || $this->team->isCoordinator($this->creator)) {
                $coordinators = $this->team->coordinators()
                    ->when($actorId, fn($q) => $q->where('users.id', '!=', $actorId))
                    ->get();
                $recipients = $recipients->merge($coordinators);
            } else {
                if ($this->creator && $this->creator->id !== $actorId) {
                    $recipients->push($this->creator);
                }
            }
        }

        $recipients = $recipients->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new \App\Notifications\TaskCompletedNotification($this, $actor));
        }
    }
}
