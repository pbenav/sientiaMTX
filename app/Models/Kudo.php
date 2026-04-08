<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kudo extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'team_id',
        'task_id',
        'type',
        'message',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
