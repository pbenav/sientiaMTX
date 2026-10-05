<?php

namespace App\Actions\Google;

use App\Models\Activity;
use App\Models\Team;
use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Support\Str;

class ImportGoogleDataAction
{
    public function __construct(protected GoogleService $googleService) {}

    public function execute(User $user, int $teamId, array $selectedEventIds, array $activityTypes): int
    {
        $syncCount = 0;
        $allowedTypes = array_keys(Activity::SUBTYPES);

        // Process Calendar Events
        $calendarIds = collect($selectedEventIds)
            ->filter(fn($id) => str_starts_with($id, 'cal:'))
            ->map(fn($id) => str_replace('cal:', '', $id))
            ->toArray();

        if (!empty($calendarIds)) {
            $allEvents = $this->googleService->listEvents(100);
            foreach ($allEvents as $event) {
                if (in_array($event->id, $calendarIds)) {
                    $chosenType = $activityTypes['cal:' . $event->id] ?? 'meeting';
                    if (!in_array($chosenType, $allowedTypes)) {
                        $chosenType = 'meeting';
                    }

                    $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
                    $fullTitle = $event->getSummary() ?: 'Evento sin título';
                    $title = mb_strlen($fullTitle) > 250 ? mb_substr($fullTitle, 0, 247) . '...' : $fullTitle;
                    
                    $description = $event->getDescription() ?: '';
                    if (mb_strlen($fullTitle) > 250) {
                        $description = "Título original: " . $fullTitle . "\n\n" . $description;
                    }

                    $existing = Activity::where('team_id', $teamId)
                        ->where(function($q) use ($event, $title, $start) {
                            $q->where('google_calendar_event_id', $event->id)
                              ->orWhere(function($sub) use ($title, $start) {
                                  $sub->where('title', $title)
                                      ->where('scheduled_date', date('Y-m-d H:i:s', strtotime($start)));
                              });
                        })->first();

                    if (!$existing) {
                        $location = $event->getLocation();
                        $meetUri = $event->getHangoutLink();
                        if (!$meetUri && $event->getConferenceData()) {
                            $entryPoints = $event->getConferenceData()->getEntryPoints();
                            if (!empty($entryPoints)) {
                                foreach ($entryPoints as $ep) {
                                    if ($ep->getEntryPointType() === 'video' || $ep->getUri()) {
                                        $meetUri = $ep->getUri();
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $isOrganizer = false;
                        if ($event->getOrganizer() && $event->getOrganizer()->getEmail() === $user->email) {
                            $isOrganizer = true;
                        }

                        $end = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();

                        $activity = new Activity([
                            'uuid'           => Str::uuid()->toString(),
                            'title'          => $title,
                            'description'    => $description,
                            'status'         => ['value' => 'scheduled'],
                            'priority'       => 'medium',
                            'type'           => $chosenType,
                            'team_id'        => $teamId,
                            'due_date'       => $end ? date('Y-m-d H:i:s', strtotime($end)) : null,
                            'scheduled_date' => date('Y-m-d H:i:s', strtotime($start)),
                            'created_by_id'  => $user->id,
                            'metadata'       => [
                                'google_is_organizer' => $isOrganizer,
                                'is_external_event' => !$isOrganizer,
                                'location' => $location,
                                'meet_uri' => $meetUri,
                                'google_html_link' => $event->getHtmlLink(),
                            ],
                            'google_calendar_event_id' => $event->id,
                        ]);
                        $activity->save();

                        $activity->assignments()->create([
                            'user_id' => $user->id,
                            'assigned_by_id' => $user->id,
                            'assigned_at' => now(),
                        ]);

                        $syncCount++;
                    } else {
                        if (!$existing->google_calendar_event_id) {
                            $existing->update(['google_calendar_event_id' => $event->id]);
                        }
                    }
                }
            }
        }

        // Process Tasks
        $taskIds = collect($selectedEventIds)
            ->filter(fn($id) => str_starts_with($id, 'tsk:'))
            ->map(fn($id) => str_replace('tsk:', '', $id))
            ->toArray();

        if (!empty($taskIds)) {
            $allTasks = $this->googleService->listTasks('@default', 100);
            foreach ($allTasks as $gTask) {
                if (in_array($gTask->getId(), $taskIds)) {
                    $chosenType = $activityTypes['tsk:' . $gTask->getId()] ?? 'task';
                    if (!in_array($chosenType, $allowedTypes)) {
                        $chosenType = 'task';
                    }

                    $fullTitle = $gTask->getTitle() ?: 'Tarea sin título';
                    $title = mb_strlen($fullTitle) > 250 ? mb_substr($fullTitle, 0, 247) . '...' : $fullTitle;

                    $description = $gTask->getNotes() ?: '';
                    if (mb_strlen($fullTitle) > 250) {
                        $description = "Título original: " . $fullTitle . "\n\n" . $description;
                    }

                    $due = $gTask->getDue();
                    
                    $existing = Activity::where('team_id', $teamId)
                        ->where(function($q) use ($gTask, $title) {
                            $q->where('google_task_id', $gTask->getId())
                              ->orWhere('title', $title);
                        })->first();

                    if (!$existing) {
                        $activity = new Activity([
                            'uuid'           => Str::uuid()->toString(),
                            'title'          => $title,
                            'description'    => $description,
                            'status'         => ['value' => $gTask->getStatus() === 'completed' ? 'completed' : 'pending'],
                            'priority'       => 'medium',
                            'type'           => $chosenType,
                            'team_id'        => $teamId,
                            'due_date'       => $due ? date('Y-m-d H:i:s', strtotime($due)) : null,
                            'created_by_id'  => $user->id,
                            'google_task_id' => $gTask->getId(),
                        ]);
                        $activity->save();

                        $activity->assignments()->create([
                            'user_id' => $user->id,
                            'assigned_by_id' => $user->id,
                            'assigned_at' => now(),
                        ]);

                        $syncCount++;
                    } else {
                        if (!$existing->google_task_id) {
                            $existing->update(['google_task_id' => $gTask->getId()]);
                        }
                    }
                }
            }
        }

        return $syncCount;
    }
}
