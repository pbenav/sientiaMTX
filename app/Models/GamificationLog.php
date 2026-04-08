<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'points',
        'type',
        'source_type',
        'source_id',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
