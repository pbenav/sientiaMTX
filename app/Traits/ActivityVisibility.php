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
     * Delega en el scope canónico visibleTo para garantizar total consistencia
     * con los filtros de privacidad profunda y permisos de equipo.
     */
    public function isVisibleTo(User $user, bool $isCoordinator = false): bool
    {
        return static::where('id', $this->id)
            ->withTrashed()
            ->visibleTo($user, $isCoordinator)
            ->exists();
    }
}
