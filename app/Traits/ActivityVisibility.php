<?php

namespace App\Traits;

use App\Models\User;

/**
 * Trait ActivityVisibility
 *
 * Proporciona métodos para verificar la visibilidad de una actividad
 * según su nivel de privacidad (public, semi-private, private)
 * y la relación del usuario con la actividad (creador, asignado, grupo).
 */
trait ActivityVisibility
{
    /**
     * Determina si la actividad es pública (visible para todo el equipo).
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Determina si la actividad es visible para el usuario dado.
     *
     * Lógica:
     * - 'public': siempre visible
     * - 'semi-private': visible para creador + asignados + grupos
     * - 'private' o NULL: solo visible para el creador
     */
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
