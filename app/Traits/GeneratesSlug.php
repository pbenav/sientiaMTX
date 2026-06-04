<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait GeneratesSlug
{
    /**
     * Boot the trait and attach events.
     */
    protected static function bootGeneratesSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = $model->generateUniqueSlug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && !empty($model->name)) {
                $model->slug = $model->generateUniqueSlug($model->name);
            }
        });
    }

    /**
     * Generate a unique slug based on a name and optional team context.
     */
    public function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        
        // Scope to team if team_id exists on the model
        $teamId = $this->team_id ?? null;
        $slug = $teamId ? "{$baseSlug}-{$teamId}" : $baseSlug;
        
        $counter = 1;
        $query = static::where('slug', $slug);
        
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }
        
        while ((clone $query)->exists()) {
            $slug = $teamId ? "{$baseSlug}-{$teamId}-{$counter}" : "{$baseSlug}-{$counter}";
            $query = static::where('slug', $slug);
            if ($this->exists) {
                $query->where('id', '!=', $this->id);
            }
            $counter++;
        }
        
        return $slug;
    }
}
