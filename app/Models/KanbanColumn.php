<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KanbanColumn extends Model
{
    protected $fillable = [
        'team_id',
        'title',
        'order_index',
        'type',
        'default_progress',
        'color',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
