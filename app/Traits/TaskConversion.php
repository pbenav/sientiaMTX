<?php

namespace App\Traits;

trait TaskConversion
{
    /**
     * Get the associated Activity if this Task has been mapped or converted to the new V2 Activity system.
     */
    public function getActivityAttribute()
    {
        $mapping = \Illuminate\Support\Facades\DB::table('activity_task_mapping')
            ->where('task_id', $this->id)
            ->first();

        if ($mapping) {
            return \App\Models\Activity::find($mapping->activity_id);
        }

        return null;
    }

    /**
     * Check if this task is an instance of a global task
     */
    public function isInstance(): bool
    {
        return !empty($this->parent_id) && !$this->is_template;
    }

    /**
     * Get all attachments associated with this task, its parent, and its children.
     */
    public function getAllAttachmentsAttribute()
    {
        return $this->attachments()
            ->with('attachable') // eager load polymorphic relationship
            ->get();
    }
}
