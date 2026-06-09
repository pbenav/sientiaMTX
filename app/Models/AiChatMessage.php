<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    protected static function booted()
    {
        // Lifecycle events are now handled by App\Observers\AiChatMessageObserver
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function taskAttachment()
    {
        return $this->belongsTo(TaskAttachment::class);
    }

    /**
     * Solo los archivos subidos al chat de IA (carpeta ai_attachments/) son propiedad del chat.
     * Los adjuntos de tareas NUNCA deben borrarse al limpiar el historial.
     */
    public static function isAiOwnedStoragePath(?string $path): bool
    {
        return $path && str_starts_with($path, 'ai_attachments/');
    }

    public function deleteOwnedFile(): void
    {
        if (self::isAiOwnedStoragePath($this->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->file_path);
        }
    }

    public function getFileUrlAttribute()
    {
        if ($this->task_attachment_id && $this->taskAttachment) {
            return $this->taskAttachment->getPublicEmbedUrl();
        }

        return $this->file_path ? \Illuminate\Support\Facades\Storage::url($this->file_path) : null;
    }
}
