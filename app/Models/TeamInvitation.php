<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
    protected $fillable = ['email', 'team_id', 'role_id', 'token'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function role()
    {
        return $this->belongsTo(TeamRole::class, 'role_id');
    }
}
