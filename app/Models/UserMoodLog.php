<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMoodLog extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'energy_level',
        'mood_label',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
