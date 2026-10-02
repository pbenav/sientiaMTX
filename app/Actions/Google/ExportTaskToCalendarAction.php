<?php

namespace App\Actions\Google;

use App\Models\Team;
use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Database\Eloquent\Model;

class ExportTaskToCalendarAction
{
    public function __construct(protected GoogleService $googleService) {}

    public function execute(User $user, Team $team, Model $task): array
    {
        $isExternalEvent = data_get($task->metadata, 'google_is_organizer') === false 
            || data_get($task->metadata, 'is_external_event') === true;

        if ($task->google_calendar_event_id) {
            if ($isExternalEvent) {
                $task->update(['google_calendar_event_id' => null]);
                return ['success' => true, 'message' => __('tasks.calendar_unlinked_external')];
            }

            try {
                $this->googleService->deleteEvent($task->google_calendar_event_id);
            } catch (\Exception $e) {
                if ($e->getCode() != 404) {
                    \Log::error("Error deleting event from Google Calendar: " . $e->getMessage());
                    return ['success' => false, 'message' => __('tasks.calendar_delete_error')];
                }
            }
            $task->update(['google_calendar_event_id' => null]);
            return ['success' => true, 'message' => __('tasks.calendar_removed_success')];
        }

        $dateToUse = $task->scheduled_date ?? $task->due_date ?? now();
        $startStr = $dateToUse->format(\DateTime::RFC3339);
        $endStr = $task->due_date ? $task->due_date->format(\DateTime::RFC3339) : $dateToUse->copy()->addHour()->format(\DateTime::RFC3339);

        $eventData = [
            'summary' => $task->title,
            'description' => $task->description ?? '',
            'start' => [
                'dateTime' => $startStr,
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $endStr,
                'timeZone' => config('app.timezone'),
            ],
        ];

        try {
            $eventId = $this->googleService->createEvent($eventData);
            if ($eventId) {
                $task->update(['google_calendar_event_id' => $eventId]);
                return ['success' => true, 'message' => __('tasks.calendar_export_success')];
            }
            return ['success' => false, 'message' => 'Error API.'];
        } catch (\Exception $e) {
            \Log::error("Error exporting to Google Calendar: " . $e->getMessage());
            return ['success' => false, 'message' => __('tasks.calendar_export_error')];
        }
    }
}
