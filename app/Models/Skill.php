<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($skill) {
            if (empty($skill->slug)) {
                $baseSlug = \Illuminate\Support\Str::slug($skill->name);
                $slug = $skill->team_id ? "{$baseSlug}-{$skill->team_id}" : $baseSlug;
                
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $skill->team_id ? "{$baseSlug}-{$skill->team_id}-{$counter}" : "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $skill->slug = $slug;
            }
        });

        static::updating(function ($skill) {
            if ($skill->isDirty('name')) {
                $baseSlug = \Illuminate\Support\Str::slug($skill->name);
                $slug = $skill->team_id ? "{$baseSlug}-{$skill->team_id}" : $baseSlug;
                
                $counter = 1;
                while (static::where('slug', $slug)->where('id', '!=', $skill->id)->exists()) {
                    $slug = $skill->team_id ? "{$baseSlug}-{$skill->team_id}-{$counter}" : "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $skill->slug = $slug;
            }
        });
    }

    protected $fillable = ['team_id', 'name', 'slug', 'description', 'color', 'icon'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills')->withPivot('level', 'total_xp')->withTimestamps();
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'skill_task');
    }

    public function scopeForTeamOrGlobal($query, $teamId)
    {
        return $query->where(function($q) use ($teamId) {
            $q->where('team_id', $teamId)
              ->orWhere(function($subQ) use ($teamId) {
                  $subQ->whereNull('team_id')
                       ->whereNotIn('name', function($nameQuery) use ($teamId) {
                           $nameQuery->select('name')
                                     ->from('skills')
                                     ->where('team_id', $teamId);
                       });
              });
        });
    }
}
