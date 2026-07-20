<?php

namespace App\Traits;

trait TaskNotifications
{
    /**
     * Notify creator and coordinators about a task event.
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
     * Notify coordinators if the task is completed and meets specific criteria.
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
