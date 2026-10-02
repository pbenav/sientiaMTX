<?php

namespace App\Actions\Activities;

use App\Models\Activity;

class RestoreMetadataAction
{
    public function execute(Activity $activity, Activity $ancestor): void
    {
        // Recuperar campos genéricos
        $activity->type = $ancestor->type;
        $activity->description = $ancestor->description;
        $activity->due_date = $ancestor->due_date;
        $activity->scheduled_date = $ancestor->scheduled_date;
        $activity->original_due_date = $ancestor->original_due_date;
        $activity->priority = $ancestor->priority;
        $activity->auto_priority = $ancestor->auto_priority;

        $currentMetadata = $activity->metadata ?? [];
        $ancestorMetadata = $ancestor->metadata ?? [];

        $internalKeys = ['converted_from_uuid', 'converted_from_id'];
        $conversionLinks = [];
        foreach ($internalKeys as $k) {
            if (isset($currentMetadata[$k])) {
                $conversionLinks[$k] = $currentMetadata[$k];
            }
        }

        unset($ancestorMetadata['converted_to_uuid'], $ancestorMetadata['converted_to_id'], $ancestorMetadata['is_deprecated']);

        $finalMetadata = array_merge($ancestorMetadata, $conversionLinks);
        $activity->metadata = $finalMetadata;

        $activity->saveQuietly();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'restored_metadata',
            'details' => json_encode(['from_uuid' => $ancestor->uuid])
        ]);
    }
}
