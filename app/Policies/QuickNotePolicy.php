<?php

namespace App\Policies;

use App\Models\QuickNote;
use App\Models\User;

/**
 * Política para QuickNote.
 *
 * Todas las operaciones requieren que el usuario sea el propietario de la nota.
 */
class QuickNotePolicy
{
    /**
     * Verifica si el usuario puede ver la nota rápida.
     */
    public function view(User $user, QuickNote $quickNote): bool
    {
        return $user->id === $quickNote->user_id;
    }

    /**
     * Verifica si el usuario puede editar la nota rápida.
     */
    public function update(User $user, QuickNote $quickNote): bool
    {
        return $user->id === $quickNote->user_id;
    }

    /**
     * Verifica si el usuario puede eliminar la nota rápida.
     */
    public function delete(User $user, QuickNote $quickNote): bool
    {
        return $user->id === $quickNote->user_id;
    }
}
