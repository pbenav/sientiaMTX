<?php

namespace App\Traits;

trait TaskVisibility
{
    public function getIsEffectivelyPrivateAttribute(): bool
    {
        $hasAssignees = $this->assigned_user_id !== null || 
                        $this->assignedTo->isNotEmpty() || 
                        $this->assignedGroups->isNotEmpty();
        
        return $hasAssignees || $this->visibility === 'private' || is_null($this->visibility);
    }

    public function getPrivacyLevelAttribute(): string
    {
        if (!$this->is_effectively_private) {
            return 'public';
        }

        $userIds = collect([$this->created_by_id, $this->assigned_user_id])->filter();
        
        if ($this->assignedTo->isNotEmpty()) {
            $userIds = $userIds->merge($this->assignedTo->pluck('id'));
        }
        
        if ($this->assignedGroups->isNotEmpty() || $userIds->unique()->count() > 1) {
            return 'semi-private';
        }

        return 'private';
    }
}
