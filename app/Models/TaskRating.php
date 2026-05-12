<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskRating extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'score',
        'comment'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
