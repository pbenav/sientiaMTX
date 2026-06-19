<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TaskAttachment;
use App\Models\Task;

class TaskAttachmentPolicy
{
    /**
     * Determine whether the user can update (rename) the attachment.
     */
    public function update(User $user, TaskAttachment $attachment): bool
    {
        return $this->delete($user, $attachment);
    }

    /**
     * Determine whether the user can delete the attachment.
     */
    public function delete(User $user, TaskAttachment $attachment): bool
    {
        // 1. Administradores Globales de la Plataforma (Pase directo)
        if ($user->is_admin) {
            return true;
        }

        // 2. Propietario original del archivo adjunto siempre puede gestionarlo
        if ($user->id === $attachment->user_id) {
            return true;
        }

        // Obtener el modelo padre (Task, ForumMessage, Expediente)
        $attachable = $attachment->attachable;
        if (!$attachable) {
            return false;
        }

        $team = $attachment->getTeam();

        // Si es el creador del equipo o coordinador/manager
        if ($team) {
            if ($team->created_by_id === $user->id) {
                return true;
            }
            if ($team->isCoordinator($user) || $team->isManager($user)) {
                return true;
            }
        }

        // Lógica específica por tipo
        if ($attachable instanceof \App\Models\Task) {
            if ($user->id === $attachable->created_by_id) {
                return true;
            }
        } elseif ($attachable instanceof \App\Models\ForumMessage) {
            if ($user->id === $attachable->user_id) {
                return true;
            }
        } elseif ($attachable instanceof \App\Models\Expediente) {
            if ($user->id === $attachable->created_by_id) {
                return true;
            }
        }

        return false;
    }
}
