<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'storage_provider',
        'provider_file_id',
        'web_view_link',
        'embed_token',
    ];

    protected $appends = ['embed_token'];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function getTeam(): ?Team
    {
        $attachable = $this->attachable;
        if (!$attachable) return null;

        if ($this->attachable_type === Task::class || $this->attachable_type === 'App\Models\Task') {
            return $attachable->team;
        }

        if ($this->attachable_type === \App\Models\ForumMessage::class || $this->attachable_type === 'App\Models\ForumMessage') {
            return $attachable->thread?->team;
        }

        if ($this->attachable_type === \App\Models\Expediente::class || $this->attachable_type === 'App\Models\Expediente') {
            return $attachable->team;
        }

        return null;
    }

    /**
     * Helper relation for when the attachment belongs to a Task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'attachable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(AttachmentLog::class, 'attachment_id');
    }

    /**
     * Token permanente para enlaces públicos de incrustación (micrositios).
     */
    public function getEmbedToken(): string
    {
        if (empty($this->attributes['embed_token'])) {
            $this->attributes['embed_token'] = bin2hex(random_bytes(32));
            $this->saveQuietly();
        }
        return $this->attributes['embed_token'];
    }

    /**
     * Accessor para el atributo embed_token.
     */
    public function getEmbedTokenAttribute(): string
    {
        return $this->getEmbedToken();
    }

    /**
     * Ruta de la copia pública para micrositios (no sustituye al original).
     */
    public function getPublicCopyPath(): string
    {
        $basename = basename($this->file_path ?: $this->file_name);

        return 'microsite_public/attachment_' . $this->id . '/' . $basename;
    }

    /**
     * Crea una copia en microsite_public/ si no existe. Nunca modifica ni elimina el original.
     */
    public function ensurePublicCopy(): ?string
    {
        if ($this->storage_provider === 'google' || !$this->file_path) {
            return null;
        }

        $copyPath = $this->getPublicCopyPath();

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($copyPath)) {
            return $copyPath;
        }

        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($this->file_path)) {
            return null;
        }

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory(dirname($copyPath));
        \Illuminate\Support\Facades\Storage::disk('public')->copy($this->file_path, $copyPath);

        return $copyPath;
    }

    /**
     * URL pública permanente para incrustar o enlazar el adjunto en micrositios.
     */
    public function getPublicEmbedUrl(): ?string
    {
        if ($this->storage_provider === 'google') {
            return $this->web_view_link
                ? str_replace('/view', '/preview', $this->web_view_link)
                : null;
        }

        if (!$this->file_path) {
            return null;
        }

        return route('public.attachments.embed', [
            'attachment' => $this->id,
            'token' => $this->getEmbedToken(),
        ]);
    }

    /**
     * Check if the physical file exists in storage.
     */
    public function getExistsAttribute(): bool
    {
        if ($this->storage_provider === 'google') return true; // Assume Drive files exist for now
        if (!$this->file_path) return false;
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Determine if the user can access this attachment within a specific team context.
     */
    public function canBeAccessedBy(User $user, Team $team): bool
    {
        $attachable = $this->attachable;
        if (!$attachable) return false;

        if ($this->attachable_type === 'App\Models\Task' || $this->attachable_type === Task::class) {
            if ($attachable->team_id !== $team->id) return false;
            
            $isManager = $team->isManager($user);
            $hasAccess = Task::where('id', $attachable->id)->visibleTo($user, $isManager)->exists();

            if (!$hasAccess && $attachable->children()->where('assigned_user_id', $user->id)->exists()) {
                $hasAccess = true;
            }

            return $hasAccess;
        }

        if ($this->attachable_type === \App\Models\ForumMessage::class || $this->attachable_type === 'App\Models\ForumMessage') {
            $thread = $attachable->thread;
            if (!$thread || $thread->team_id !== $team->id) return false;

            // Check if user is member of the team
            if (!$team->members()->where('users.id', $user->id)->exists()) {
                return false;
            }

            // Private message restriction
            if ($attachable->is_private) {
                $task = $thread->task;
                if (!$task) return false;

                return $task->assignedTo()->where('users.id', $user->id)->exists() || 
                       $task->created_by_id === $user->id || 
                       $task->assigned_user_id === $user->id ||
                       $team->isCoordinator($user);
            }

            return true;
        }

        if ($this->attachable_type === \App\Models\Expediente::class || $this->attachable_type === 'App\Models\Expediente') {
            if ($attachable->team_id !== $team->id) return false;
            return $team->members()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Check if attachment is compatible with OnlyOffice editor.
     */
    public function getIsOfficeCompatibleAttribute(): bool
    {
        if ($this->storage_provider === 'google') return false;
        
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        $map = config('onlyoffice.extensions', []);
        
        $allExtensions = array_merge(
            $map['word'] ?? [],
            $map['cell'] ?? [],
            $map['slide'] ?? []
        );
        
        return in_array($ext, $allExtensions);
    }
}
