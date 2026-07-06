<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Policies;

use App\Models\User;
use App\Models\ActivityAttachment;

class ActivityAttachmentPolicy
{
    /**
     * Determine whether the user can update (rename) the attachment.
     */
    public function update(User $user, ActivityAttachment $attachment): bool
    {
        return $this->delete($user, $attachment);
    }

    /**
     * Determine whether the user can delete the attachment.
     */
    public function delete(User $user, ActivityAttachment $attachment): bool
    {
        // 1. Administradores Globales de la Plataforma (Pase directo)
        if ($user->is_admin) {
            return true;
        }

        // 2. Propietario original del archivo adjunto siempre puede gestionarlo
        if ($user->id === $attachment->uploaded_by_id) {
            return true;
        }

        // Obtener la actividad asociada
        $activity = $attachment->activity;
        if (!$activity) {
            return false;
        }

        $team = $activity->team;

        // Si es el creador del equipo o coordinador/manager
        if ($team) {
            if ($team->created_by_id === $user->id) {
                return true;
            }
            if ($team->isCoordinator($user) || $team->isManager($user)) {
                return true;
            }
        }

        // Si es el creador de la actividad
        if ($user->id === $activity->created_by_id) {
            return true;
        }

        return false;
    }
}
