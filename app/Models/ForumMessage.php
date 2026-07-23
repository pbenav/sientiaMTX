<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mensaje de foro con cadena de respuestas.
 *
 * Representa un mensaje dentro de un hilo de foro, con soporte para
 * respuestas anidadas (parent_id), mensajes privados, edición,
 * adjuntos polimórficos y sistema de votos.
 *
 * Campos clave:
 * - forum_thread_id: ID del hilo al que pertenece el mensaje
 * - parent_id: ID del mensaje padre (null para mensajes principales)
 * - user_id: ID del usuario que escribió el mensaje
 * - content: Contenido del mensaje
 * - is_edited: Si el mensaje ha sido editado
 * - is_private: Si el mensaje es privado (solo visible por el autor)
 *
 * @property-read int $forum_thread_id
 * @property-read int|null $parent_id
 * @property-read int $user_id
 * @property-read string $content
 * @property-read bool $is_edited
 * @property-read bool $is_private
 *
 * @property-read \App\Models\ForumThread $thread
 * @property-read \App\Models\ForumMessage|null $parent
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class ForumMessage extends Model
{
    protected $fillable = [
        'forum_thread_id',
        'parent_id',
        'user_id',
        'content',
        'is_edited',
        'is_private',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'is_private' => 'boolean',
    ];

    /**
     * Relación de pertenencia al hilo del foro.
     *
     * @return BelongsTo<\App\Models\ForumThread, $this>
     */
    public function thread()
    {
        return $this->belongsTo(ForumThread::class, 'forum_thread_id');
    }

    /**
     * Relación de pertenencia al mensaje padre (respuesta).
     *
     * @return BelongsTo<\App\Models\ForumMessage, $this>
     */
    public function parent()
    {
        return $this->belongsTo(ForumMessage::class, 'parent_id');
    }

    /**
     * Relación uno-a-muchos con las respuestas al mensaje.
     *
     * Filtra las respuestas visibles según los permisos del usuario
     * y el equipo, incluyendo bypass para coordinadores y miembros
     * de tareas relacionadas.
     *
     * @param int|null $userId ID del usuario que consulta (null = usuario autenticado)
     * @param \App\Models\Team|null $team Equipo del usuario (null = del request)
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\ForumMessage>
     */
    public function replies($userId = null, $team = null)
    {
        if ($userId === null) {
            $userId = auth()->id();
        }

        if ($team === null) {
            $team = request()?->route('team');
        }

        return $this->hasMany(ForumMessage::class, 'parent_id')
            ->where(function($query) use ($userId, $team) {
                $query->where('is_private', false)
                    ->orWhere('user_id', $userId);

                if ($team) {
                    // Coordinator bypass
                    if ($team->isCoordinator(auth()->user())) {
                        return $query;
                    }

                    // Task-related bypass
                    $thread = $this->thread;
                    if ($thread && $thread->task_id) {
                        $task = $thread->task;
                        if ($task) {
                            $query->orWhere(function($q) use ($task, $userId) {
                                $q->whereRaw('? IN (SELECT user_id FROM task_assignments WHERE task_id = ? AND user_id IS NOT NULL)', [$userId, $task->id])
                                  ->orWhereRaw('? = (SELECT created_by_id FROM tasks WHERE id = ?)', [$userId, $task->id])
                                  ->orWhereRaw('? = (SELECT assigned_user_id FROM tasks WHERE id = ?)', [$userId, $task->id])
                                  ->orWhereRaw('EXISTS (SELECT 1 FROM group_user gu JOIN task_assignments ta ON gu.group_id = ta.group_id WHERE gu.user_id = ? AND ta.task_id = ?)', [$userId, $task->id]);
                            });
                        }
                    }
                }
            })
            ->orderBy('created_at', 'asc');
    }

    /**
     * Relación de pertenencia al usuario que escribió el mensaje.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación polimórfica uno-a-muchos con los adjuntos del mensaje.
     *
     * @return MorphMany<\App\Models\TaskAttachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }

    /**
     * Relación muchos-a-muchos con los usuarios que votaron por el mensaje.
     *
     * @return BelongsToMany<\App\Models\User, $this>
     */
    public function votes()
    {
        return $this->belongsToMany(User::class, 'forum_message_votes', 'forum_message_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Verifica si un usuario votó por este mensaje.
     *
     * @param \App\Models\User|int|null $user Usuario o su ID
     * @return bool True si el usuario votó por el mensaje
     */
    public function hasVotedBy($user)
    {
        if (!$user) return false;
        $userId = $user instanceof User ? $user->id : $user;
        return $this->votes()->where('user_id', $userId)->exists();
    }
}
