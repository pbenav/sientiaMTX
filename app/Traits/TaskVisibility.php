<?php

namespace App\Traits;

trait TaskVisibility
{
    /**
     * Determina si la tarea es efectivamente privada según sus asignaciones y visibilidad.
     */
    public function getIsEffectivelyPrivateAttribute(): bool
    {
        $hasAssignees = $this->assigned_user_id !== null || 
                        $this->assignedTo->isNotEmpty() || 
                        $this->assignedGroups->isNotEmpty();
        
        return $hasAssignees || $this->visibility === 'private' || is_null($this->visibility);
    }

    /**
     * Retorna el nivel de privacidad de la tarea: 'public', 'semi-private' o 'private'.
     */
    public function getPrivacyLevelAttribute(): string
    {
        if (!$this->is_effectively_private) {
            return 'public';
        }

        $userIds = collect([$this->created_by_id, $this->assigned_user_id])->filter();
        
        if ($this->assignedTo->isNotEmpty()) {
            $userIds = $userIds->merge($this->assignedTo->pluck('id'));
        }
        
        if ($this->assignedGroups->isNotEmpty() || $userIds->unique()->count() > 1) {
            return 'semi-private';
        }

        return 'private';
    }
}
