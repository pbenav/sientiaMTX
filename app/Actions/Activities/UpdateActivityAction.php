<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\Team;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class UpdateActivityAction
{
    public function __construct(protected ActivityService $activityService) {}

    public function execute(Team $team, Activity $activity, array $validated, Request $request, bool $isParallel)
    {
        if ($request->has('auto_priority')) {
            $validated['auto_priority'] = $request->boolean('auto_priority');
        } elseif (in_array($activity->type, ['task', 'meeting', 'reminder'])) {
            $validated['auto_priority'] = false;
        }

        if ($isParallel) {
            if (isset($validated['metadata'])) {
                unset(
                    $validated['metadata']['is_external_event'],
                    $validated['metadata']['google_is_organizer'],
                    $validated['metadata']['google_organizer_email'],
                    $validated['metadata']['google_organizer_name'],
                    $validated['metadata']['google_calendar_event_id'],
                    $validated['metadata']['google_calendar_id'],
                    $validated['metadata']['google_html_link'],
                    $validated['metadata']['google_synced_at'],
                    $validated['metadata']['google_meet_url'],
                    $validated['metadata']['google_task_id'],
                    $validated['metadata']['google_task_list_id']
                );
            }

            $validated['metadata']['parallel_of_activity_id'] = $activity->id;
            $type = $validated['type'] ?? $activity->type;

            if (empty($validated['scheduled_date']) && $activity->scheduled_date) {
                $validated['scheduled_date'] = $activity->scheduled_date;
            }
            if (empty($validated['due_date']) && $activity->due_date) {
                $validated['due_date'] = $activity->due_date;
            }

            return $this->activityService->create(
                $team,
                $type,
                $validated,
                $request->file('attachments') ?? [],
                $request->input('drive_attachments')
            );
        }

        // Integrity Protection for agreements
        if ($activity->type === 'agreement') {
            $meta = $activity->metadata ?? [];
            $hasMemberSig = collect($meta['member_signatures'] ?? [])->contains(fn($s) => !empty($s['signed_at']));
            $hasGuestSig  = collect($meta['guests'] ?? [])->contains(fn($g) => !empty($g['signed_at']));

            if ($hasMemberSig || $hasGuestSig) {
                if (isset($validated['metadata']['terms'])) {
                    unset($validated['metadata']['terms']);
                }
            }
        }

        return $this->activityService->update(
            $activity,
            $validated,
            $request->file('attachments') ?? [],
            $request->input('drive_attachments')
        );
    }
}
