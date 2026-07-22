<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\Team;
use Illuminate\Support\Str;

class CloneActivityAction
{
    public function execute(Activity $activity, int $userId): Activity
    {
        $newUuid = Str::uuid()->toString();
        $metadata = $activity->metadata ?? [];
        unset($metadata['converted_to_uuid'], $metadata['converted_to_id'], $metadata['is_deprecated']);
        $metadata['cloned_from_uuid'] = $activity->uuid;

        $cloned = Activity::create([
            'uuid' => $newUuid,
            'team_id' => $activity->team_id,
            'created_by_id' => $userId,
            'parent_id' => $activity->parent_id,
            'expediente_id' => $activity->expediente_id,
            'type' => $activity->type,
            'title' => $activity->title . ' (Clon)',
            'description' => $activity->description,
            'status' => ['value' => 'pending'],
            'metadata' => $metadata,
            'visibility' => $activity->visibility,
            'due_date' => $activity->due_date,
            'scheduled_date' => $activity->scheduled_date,
            'priority' => $activity->priority,
            'is_archived' => false,
            'is_template' => $activity->is_template,
        ]);

        foreach ($activity->assignments as $assignment) {
            $cloned->assignments()->create([
                'user_id' => $assignment->user_id,
                'group_id' => $assignment->group_id,
                'assigned_by_id' => $userId,
                'assigned_at' => now(),
            ]);
        }

        foreach ($activity->tags as $tag) {
            $cloned->tags()->create(['tag_id' => $tag->tag_id]);
        }

        $cloned->histories()->create([
            'user_id' => $userId,
            'action' => 'cloned_from_deprecated',
            'details' => json_encode(['from_activity_id' => $activity->id, 'from_uuid' => $activity->uuid])
        ]);

        return $cloned;
    }
}
