<?php

namespace App\Traits;

use App\Models\User;

trait ActivityVisibility
{
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->visibility === 'public') return true;
        
        // Creador siempre ve su actividad
        if ($this->created_by_id === $user->id) return true;
        
        // 'semi-private': creador + asignados
        if (in_array($this->visibility, ['semi-private', 'semiprivate'])) {
            return $this->assignedTo->contains('id', $user->id)
                || $this->assignedGroups->filter(fn($g) => $g->users->contains('id', $user->id))->isNotEmpty();
        }
        
        // 'private' o NULL: solo creador (ya verificado arriba)
        return false;
    }
}
