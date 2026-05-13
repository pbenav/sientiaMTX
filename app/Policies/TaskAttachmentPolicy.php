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
        // 1. Propietario original del archivo adjunto siempre puede gestionarlo
        if ($user->id === $attachment->user_id) {
            return true;
        }

        // Extraer la tarea a la que pertenece el adjunto
        $task = $attachment->attachable;
        if (!$task || !($task instanceof Task)) {
            return false;
        }

        // 2. Coordinadores y Managers del equipo (Filosofía A - Jerarquía y Seguridad)
        if ($task->team && ($task->team->isCoordinator($user) || $task->team->isManager($user))) {
            return true;
        }

        // 3. El creador de la Tarea / Plan Maestro (Dueño del contenedor)
        if ($user->id === $task->created_by_id) {
            return true;
        }

        // ── PREPARADO PARA FUTURAS FILOSOFÍAS ──
        // Si en el futuro quieres activar la opción B (Cualquier asignado) mediante una configuración:
        // $isAssigned = $user->id === $task->assigned_user_id || $task->assignedTo()->where('users.id', $user->id)->exists();
        // if ($isAssigned && $task->team->settings->attachment_policy === 'open') { return true; }

        return false;
    }
}
