<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    public function thread()
    {
        return $this->belongsTo(ForumThread::class, 'forum_thread_id');
    }

    public function parent()
    {
        return $this->belongsTo(ForumMessage::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumMessage::class, 'parent_id')
            ->where(function($query) {
                $userId = auth()->id();
                $team = request()->route('team'); // Get current team from route
                
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }
}
