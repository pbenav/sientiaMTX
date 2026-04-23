<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'task_id',
        'title',
        'is_pinned',
        'is_locked',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function messages()
    {
        return $this->hasMany(ForumMessage::class);
    }

    /**
     * Scope for threads that have no linked task (Knowledge Library).
     */
    public function scopeOrphaned($query)
    {
        return $query->whereNull('task_id');
    }

    /**
     * Determine if this thread is part of the "Knowledge Library" (orphaned).
     */
    public function isKnowledgeLibrary(): bool
    {
        return is_null($this->task_id);
    }
}
