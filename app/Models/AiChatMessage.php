<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mensaje de chat con IA con manejo de archivos.
 *
 * Registra las interacciones del usuario con el asistente de IA,
 * incluyendo mensajes de texto, archivos adjuntos y el rol del
 * remitente (user, assistant, system).
 *
 * Campos clave:
 * - user_id: ID del usuario que envió el mensaje
 * - team_id: ID del equipo asociado
 * - task_id: ID de la tarea relacionada (si aplica)
 * - task_attachment_id: ID del adjunto de tarea vinculado
 * - role: Rol del remitente (user, assistant, system)
 * - content: Contenido del mensaje
 * - file_path: Ruta del archivo adjunto (si aplica)
 * - file_name: Nombre del archivo adjunto
 * - file_type: Tipo MIME del archivo adjunto
 *
 * @property-read int $user_id
 * @property-read int|null $team_id
 * @property-read int|null $task_id
 * @property-read int|null $task_attachment_id
 * @property-read string $role
 * @property-read string $content
 * @property-read string|null $file_path
 * @property-read string|null $file_name
 * @property-read string|null $file_type
 * @property-read string|null $file_url
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\TaskAttachment|null $taskAttachment
 *
 * @mixin Builder
 */
class AiChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'task_id',
        'task_attachment_id',
        'role',
        'content',
        'file_path',
        'file_name',
        'file_type',
    ];

    protected $appends = ['file_url'];

    /**
     * Relación de pertenencia al usuario que envió el mensaje.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al adjunto de tarea vinculado.
     *
     * @return BelongsTo<\App\Models\TaskAttachment, $this>
     */
    public function taskAttachment()
    {
        return $this->belongsTo(TaskAttachment::class);
    }

    /**
     * Verifica si una ruta pertenece al almacenamiento propio del chat IA.
     *
     * Solo los archivos subidos al chat de IA (carpeta ai_attachments/)
     * son propiedad del chat. Los adjuntos de tareas NUNCA deben borrarse
     * al limpiar el historial.
     *
     * @param string|null $path Ruta a verificar
     * @return bool True si la ruta pertenece al almacenamiento del chat IA
     */
    public static function isAiOwnedStoragePath(?string $path): bool
    {
        return $path && str_starts_with($path, 'ai_attachments/');
    }

    /**
     * Elimina el archivo adjunto asociado al mensaje si es propiedad del chat IA.
     *
     * Solo elimina archivos cuya ruta comienza con 'ai_attachments/'.
     */
    public function deleteOwnedFile(): void
    {
        if (self::isAiOwnedStoragePath($this->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->file_path);
        }
    }

    /**
     * Atributo accesible: URL pública del archivo adjunto.
     *
     * Si el mensaje tiene un adjunto de tarea vinculado, devuelve la
     * URL de incrustación pública de ese adjunto. De lo contrario,
     * devuelve la URL del archivo en el almacenamiento público.
     *
     * @return string|null URL pública del archivo
     */
    public function getFileUrlAttribute()
    {
        if ($this->task_attachment_id && $this->taskAttachment) {
            return $this->taskAttachment->getPublicEmbedUrl();
        }

        return $this->file_path ? \Illuminate\Support\Facades\Storage::url($this->file_path) : null;
    }
}
