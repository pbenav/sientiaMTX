<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = [
        'name',
        'description',
        'icon',
        'criteria_type',
        'criteria_threshold',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('awarded_at', 'team_id')->withTimestamps();
    }
}
