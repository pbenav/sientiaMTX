<?php

namespace App\Observers;

use App\Models\TaskAttachment;

class TaskAttachmentObserver
{
    /**
     * Handle the TaskAttachment "created" event.
     */
    public function created(TaskAttachment $attachment): void
    {
        // Only count if it's NOT a Google Drive file
        if ($attachment->storage_provider !== 'google') {
            $team = $attachment->getTeam();
            if ($team) {
                $team->increment('disk_used', $attachment->file_size);
                // Refresh usage and check for alerts
                $team->refresh()->checkStorageAlerts();
            }
        }
    }

    /**
     * Handle the TaskAttachment "updated" event.
     */
    public function updated(TaskAttachment $attachment): void
    {
        // Handle cases where a file might be moved to Drive later
        if ($attachment->wasChanged('storage_provider')) {
            $team = $attachment->getTeam();
            if ($team) {
                if ($attachment->storage_provider === 'google') {
                    // Just moved to Drive, subtract previous size
                    $team->decrement('disk_used', max(0, $attachment->getOriginal('file_size')));
                } else if ($attachment->getOriginal('storage_provider') === 'google') {
                    // Moved from Drive to local (unlikely but possible), add size
                    $team->increment('disk_used', $attachment->file_size);
                }
            }
        }
    }

    /**
     * Handle the TaskAttachment "deleted" event.
     */
    public function deleted(TaskAttachment $attachment): void
    {
        // Only decrement if it was NOT a Google Drive file
        if ($attachment->storage_provider !== 'google') {
            $team = $attachment->getTeam();
            if ($team) {
                $team->decrement('disk_used', max(0, $attachment->file_size));
            }
        }
    }
}
