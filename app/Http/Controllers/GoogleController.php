<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

use App\Traits\AwardsGamification;

class GoogleController extends Controller
{
    use AwardsGamification;

    protected $googleService;

    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
    }

    /**
     * Redirect to Google for authentication.
     */
    public function redirect(Request $request)
    {
        if (!$this->googleService->isConfigured()) {
            return redirect()->route('dashboard')->with('error', __('google.not_configured'));
        }

        $state = [];
        if ($request->has('popup')) {
            $state['popup'] = 1;
            session(['google_auth_is_popup' => true]);
        }
        if ($request->has('team_id')) $state['team_id'] = $request->team_id;

        if (!empty($state)) {
            $this->googleService->getClient()->setState(json_encode($state));
        }

        return redirect()->away($this->googleService->getClient()->createAuthUrl());
    }

    /**
     * Handle the callback from Google.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('dashboard')->with('error', __('google.auth_failed', ['error' => $request->error]));
        }

        if (!$request->has('code')) {
            return redirect()->route('dashboard')->with('error', __('google.invalid_callback'));
        }

        try {
            $token = $this->googleService->getClient()->fetchAccessTokenWithAuthCode($request->code);
            Log::info("Google Callback: Token fetched success", ['has_refresh' => isset($token['refresh_token'])]);

            if (isset($token['error'])) {
                Log::error("Google Callback: Token error", ['error' => $token['error']]);
                return redirect()->route('dashboard')->with('error', __('google.token_error', ['error' => $token['error']]));
            }

            $user = Auth::user();
            if (!$user) {
                Log::error("Google Callback: NO USER LOGGED IN");
                return redirect()->route('login')->with('error', 'Sesión expirada');
            }

            $stateData = json_decode($request->state, true) ?? [];
            $teamId = $stateData['team_id'] ?? session('google_auth_team_id');
            Log::info("Google Callback: Context Info", ['user' => $user->id, 'team' => $teamId]);

            // Reliable popup detection via session + state fallback
            $isPopup = session()->pull('google_auth_is_popup', false) || ($stateData['popup'] ?? false);

            // Fetch Google ID for reference
            $oauth2 = new \Google\Service\Oauth2($this->googleService->getClient());
            $userInfo = $oauth2->userinfo->get();
            Log::info("Google Callback: User Info", ['google_id' => $userInfo->id]);

            $refreshToken = $token['refresh_token'] ?? null;

            if ($teamId) {
                // Recover refresh token if missing
                if (!$refreshToken) {
                    $existing = $user->teams()->find($teamId);
                    $refreshToken = $existing ? $existing->pivot->google_refresh_token : null;
                    Log::info("Google Callback: Recovered Refresh Token from Pivot", ['success' => !!$refreshToken]);
                }

                $user->teams()->updateExistingPivot($teamId, [
                    'google_id' => $userInfo->id,
                    'google_email' => $userInfo->email,
                    'google_token' => $token, 
                    'google_refresh_token' => $refreshToken,
                ]);
                Log::info("Google Callback: UPDATED PIVOT for team: $teamId");
            }

            $user->save(); 
            $user->refresh(); 

            if ($isPopup) {
                Log::info("Google Callback: Closing Popup Window");
                return view('google.callback-success');
            }

            return Redirect::route('profile.edit', [
                'tab' => 'integrations',
                'team_id' => $teamId
            ])->with('status', 'google-connected');
        } catch (\Exception $e) {
            Log::error('Google callback exception: ' . $e->getMessage());
            
            if ($isPopup) {
                $errorMsg = addslashes($e->getMessage());
                return "<html><body><script>alert(\"Authentication failed: {$errorMsg}\"); window.close();</script></body></html>";
            }

            return redirect()->route('dashboard')->with('error', __('google.auth_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Show events (Calendar events) for the current user to select.
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        
        $teamId = $request->input('team_id');
        if (!$teamId) {
            return back()->with('error', __('google.team_id_required'));
        }

        if (!$this->googleService->setTokenForUser($user, $teamId)) {
            return redirect()->route('google.auth', ['team_id' => $teamId])->with('info', __('google.connect_account_first'));
        }

        $team = \App\Models\Team::findOrFail($teamId);
        
        // Fetch Calendar Events
        $events = $this->googleService->listEvents(50);
        $eventsData = collect($events)->map(function($event) use ($teamId, $user) {
            $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
            $title = $event->getSummary();
            
            $exists = \App\Models\Task::where('team_id', $teamId)
                ->where('created_by_id', $user->id)
                ->where('title', $title)
                ->where('scheduled_date', date('Y-m-d H:i:s', strtotime($start)))
                ->exists();

            return [
                'id' => 'cal:' . $event->id,
                'title' => $title,
                'description' => $event->getDescription() ?: '',
                'start' => $start,
                'end' => $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate(),
                'exists' => $exists,
                'type' => 'calendar'
            ];
        });

        // Fetch Google Tasks
        $tasks = $this->googleService->listTasks(50);
        $tasksData = collect($tasks)->map(function($task) use ($teamId, $user) {
            $due = $task->getDue() ?: now()->toIso8601String();
            $title = $task->getTitle();
            $googleId = 'task:' . $task->id;
            
            // Robust matching: prioritized by google_task_id, then by title+date (ignoring exact time)
            $exists = \App\Models\Task::where('team_id', $teamId)
                ->where(function($q) use ($googleId, $title, $due) {
                    $q->where('google_task_id', $googleId)
                      ->orWhere(function($sub) use ($title, $due) {
                          $sub->where('title', 'LIKE', $title . '%')
                              ->whereDate('scheduled_date', date('Y-m-d', strtotime($due)));
                      });
                })
                ->exists();

            return [
                'id' => $googleId,
                'title' => $title,
                'description' => ($task->getNotes() ?: '') . ($task->listTitle ? " [" . $task->listTitle . "]" : ""),
                'start' => $due,
                'end' => $due,
                'exists' => $exists,
                'type' => 'task'
            ];
        });

        // Combine and sort by date
        $combined = $eventsData->concat($tasksData)->sortBy('start');

        return view('google.select-tasks', [
            'events' => $combined,
            'team' => $team,
            'visibility' => $request->input('visibility', 'private')
        ]);
    }

    /**
     * Import selected tasks.
     */
    public function import(Request $request)
    {
        $user = Auth::user();
        $teamId = $request->input('team_id');
        $selectedEventIds = $request->input('events', []);
        $visibility = $request->input('visibility', 'private');

        if (empty($selectedEventIds)) {
            return back()->with('error', __('google.no_tasks_selected'));
        }

        if (!$this->googleService->setTokenForUser($user, $teamId)) {
            return redirect()->route('google.auth', ['team_id' => $teamId])->with('info', __('google.connect_account_first'));
        }

        $syncCount = 0;

        // Process Calendar Events
        $calendarIds = collect($selectedEventIds)->filter(fn($id) => str_starts_with($id, 'cal:'))->map(fn($id) => str_replace('cal:', '', $id))->toArray();
        if (!empty($calendarIds)) {
            $allEvents = $this->googleService->listEvents(100);
            foreach ($allEvents as $event) {
                if (in_array($event->id, $calendarIds)) {
                    $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
                    $title = $event->getSummary();

                    $existing = \App\Models\Task::where('team_id', $teamId)
                        ->where('created_by_id', $user->id)
                        ->where('title', $title)
                        ->where('scheduled_date', date('Y-m-d H:i:s', strtotime($start)))
                        ->first();

                    if (!$existing) {
                        $taskModel = \App\Models\Task::create([
                            'team_id' => $teamId,
                            'title' => $title,
                            'description' => $event->getDescription() ?: '',
                            'scheduled_date' => date('Y-m-d H:i:s', strtotime($start)),
                            'due_date' => $event->getEnd()->getDateTime() ? date('Y-m-d H:i:s', strtotime($event->getEnd()->getDateTime())) : null,
                            'created_by_id' => $user->id,
                            'assigned_user_id' => $user->id,
                            'visibility' => $visibility,
                            'priority' => 'low',
                            'urgency' => 'low',
                            'status' => 'pending',
                        ]);
                        $syncCount++;
                    }
                }
            }
        }

        // Process Google Tasks
        $taskIds = collect($selectedEventIds)->filter(fn($id) => str_starts_with($id, 'task:'))->map(fn($id) => str_replace('task:', '', $id))->toArray();
        if (!empty($taskIds)) {
            $allTasks = $this->googleService->listTasks(100);
            foreach ($allTasks as $task) {
                if (in_array($task->id, $taskIds)) {
                    $due = $task->getDue() ?: now()->toIso8601String();
                    $title = $task->getTitle();

                    $existing = \App\Models\Task::where('team_id', $teamId)
                        ->where('created_by_id', $user->id)
                        ->where('title', $title)
                        ->where('scheduled_date', date('Y-m-d H:i:s', strtotime($due)))
                        ->first();

                    if (!$existing) {
                        $taskModel = \App\Models\Task::create([
                            'team_id' => $teamId,
                            'title' => $title,
                            'description' => ($task->getNotes() ?: '') . ($task->listTitle ? " [" . $task->listTitle . "]" : ""),
                            'scheduled_date' => date('Y-m-d H:i:s', strtotime($due)),
                            'due_date' => date('Y-m-d H:i:s', strtotime($due)),
                            'created_by_id' => $user->id,
                            'assigned_user_id' => $user->id,
                            'visibility' => $visibility,
                            'priority' => 'low',
                            'urgency' => 'low',
                            'status' => $task->getStatus() === 'completed' ? 'completed' : 'pending',
                        ]);

                        if ($taskModel->status === 'completed') {
                            $this->awardGamificationPoints($taskModel);
                        }

                        $syncCount++;
                    }
                }
            }
        }

        return redirect()->route('teams.dashboard', $teamId)
            ->with('success', __('google.import_success', ['count' => $syncCount]));
    }

    /**
     * Disconnect Google account (clear tokens).
     */
    public function disconnect(Request $request)
    {
        $user = auth()->user();
        $teamId = $request->query('team_id') ?? $request->input('team_id');

        if ($teamId) {
            $user->teams()->updateExistingPivot($teamId, [
                'google_id' => null,
                'google_email' => null,
                'google_token' => null,
                'google_refresh_token' => null,
            ]);
            return Redirect::route('profile.edit', [
                'tab' => 'integrations',
                'team_id' => $teamId
            ])->with('status', 'google-team-disconnected');
        }

        // Legacy/Global disconnect
        $user->google_id = null;
        $user->google_email = null;
        $user->google_token = null;
        $user->google_refresh_token = null;
        $user->save();

        return Redirect::route('profile.edit', ['tab' => 'integrations'])->with('status', 'google-disconnected');
    }

    /**
     * Sync a specific task with Google Tasks (Bidirectional).
     */
    public function syncTask(\App\Models\Team $team, \App\Models\Task $task)
    {
        $user = Auth::user();

        if (!$this->googleService->setTokenForUser($user, $team->id)) {
            return redirect()->route('google.auth', ['team_id' => $team->id])->with('info', __('google.connect_account_first'));
        }

        // 1. If not exported yet, export it
        if (!$task->google_task_id) {
            $notes = ($task->description ?: '') . "\n\n";
            $notes .= "--- SientiaMTX Details ---\n";
            $notes .= "Quadrant: " . $task->getQuadrant($task) . "\n";
            $notes .= "Priority: " . strtoupper($task->priority) . "\n";
            $notes .= "Urgency: " . strtoupper($task->urgency) . "\n";
            $notes .= "Team: " . $team->name . "\n";

            $dateToUse = $task->due_date ?? $task->scheduled_date;

            $data = [
                'title' => $task->title,
                'notes' => trim($notes),
            ];

            if ($dateToUse) {
                // Google Tasks API expects RFC3339 for the due field
                $data['due'] = $dateToUse->toRfc3339String();
            }

            try {
                $googleTaskId = $this->googleService->createTask($data);

                if ($googleTaskId) {
                    $task->update([
                        'google_task_id' => $googleTaskId,
                        'google_task_list_id' => '@default',
                        'google_synced_at' => now(),
                    ]);
                    return back()->with('success', __('google.export_success'));
                }
            } catch (\Exception $e) {
                Log::error('Error exporting to Google Tasks: ' . $e->getMessage());
                return back()->with('error', __('google.export_failed') . ': ' . $e->getMessage());
            }
        }

        // 2. Already exported, perform bidirectional sync
        try {
            $googleTask = $this->googleService->getTask($task->google_task_list_id, $task->google_task_id);

            if (!$googleTask) {
                // Task was deleted in Google Tasks. Unlink it locally instead of deleting.
                $task->update([
                    'google_task_id' => null,
                    'google_task_list_id' => null,
                    'google_synced_at' => null,
                ]);
                return redirect()->route('teams.tasks.show', [$team, $task])
                    ->with('warning', __('google.sync_remote_unlinked'));
            }

            $googleUpdated = strtotime($googleTask->getUpdated());
            $localUpdated = $task->updated_at->timestamp;
            $lastSynced = $task->google_synced_at ? $task->google_synced_at->timestamp : 0;

            // Determine which side is newer
            // If Google is newer than the last sync AND newer than local
            if ($googleUpdated > $lastSynced && $googleUpdated > $localUpdated) {
                $oldTitle = $task->title;
                $newTitle = $googleTask->getTitle();
                $titleChanged = ($oldTitle !== $newTitle);

                // Remote is newer, update local
                $task->update([
                    'title' => $newTitle,
                    'description' => $googleTask->getNotes() ?: $task->description,
                    'status' => $googleTask->getStatus() === 'completed' ? 'completed' : $task->status,
                    'progress_percentage' => $googleTask->getStatus() === 'completed' ? 100 : $task->progress_percentage,
                    'google_synced_at' => now(),
                ]);
                
                // --- Title propagation (Architectural requirement) ---
                if ($titleChanged) {
                    if ($task->is_template) {
                        // If template name changes, all instances follow
                        $task->instances()->update(['title' => $newTitle]);
                    } elseif ($task->parent_id) {
                        // If an instance name changes, we update the parent name and all siblings
                        $parent = $task->parent;
                        $parent->update(['title' => $newTitle]);
                        $parent->instances()->where('id', '!=', $task->id)->update(['title' => $newTitle]);
                    }
                }

                // If it was marked as completed in Google, ensure local status reflects it
                if ($googleTask->getStatus() === 'completed' && $task->status !== 'completed') {
                    $task->status = 'completed';
                    $task->progress_percentage = 100;
                    $task->save();
                    $this->awardGamificationPoints($task);
                } else {
                    $task->save();
                }
                
                // --- Parent sync (Architectural requirement) ---
                if ($task->parent_id) {
                    $currentParent = $task->parent;
                    while ($currentParent) {
                        $currentParent->update(['progress_percentage' => $currentParent->progress]);
                        $currentParent->syncKanbanColumn();
                        $currentParent = $currentParent->parent;
                    }
                }

                return back()->with('success', __('google.sync_from_remote_success'));
            } 
            
            // If Local is newer than last sync
            if ($localUpdated > $lastSynced) {
                // Local is newer, update remote
                $notes = ($task->description ?: '') . "\n\n";
                $notes .= "--- " . __('google.details_title') . " ---\n";
                $q = $task->getQuadrant($task);
                $notes .= __('google.details_quadrant') . ": Q{$q} - " . __('tasks.quadrants.' . $q . '.label') . "\n";
                $notes .= __('google.details_priority') . ": " . strtoupper(__('tasks.priorities.' . $task->priority)) . "\n";
                $notes .= __('google.details_urgency') . ": " . strtoupper(__('tasks.urgencies.' . $task->urgency)) . "\n";
                $notes .= __('google.details_team') . ": " . $team->name . "\n";

                $dateToUse = $task->due_date ?? $task->scheduled_date;

                $data = [
                    'title' => $task->title,
                    'notes' => trim($notes),
                    'status' => $task->status === 'completed' ? 'completed' : 'needsAction',
                ];
                
                if ($dateToUse) {
                    $data['due'] = $dateToUse->toRfc3339String();
                }

                $this->googleService->updateTask($task->google_task_list_id, $task->google_task_id, $data);
                
                $task->update([
                    'google_synced_at' => now(),
                ]);

                return back()->with('success', __('google.sync_to_remote_success'));
            }

            return back()->with('info', __('google.already_synced'));
        } catch (\Exception $e) {
            Log::error('Error in bidirectional Google Tasks sync: ' . $e->getMessage());
            return back()->with('error', __('google.sync_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Export a specific task to Google Calendar as an Event.
     */
    public function exportTaskToCalendar(\App\Models\Team $team, \App\Models\Task $task)
    {
        $user = auth()->user();

        if (!$this->googleService->setTokenForUser($user, $team->id)) {
            return redirect()->route('google.auth', ['team_id' => $team->id])->with('info', __('google.connect_account_first'));
        }

        // Toggle: If already exported, delete it
        if ($task->google_calendar_event_id) {
            try {
                if ($this->googleService->deleteEvent($task->google_calendar_event_id)) {
                    $task->update([
                        'google_calendar_event_id' => null,
                        'google_calendar_id' => null,
                    ]);
                    return back()->with('success', __('google.calendar_removed_success'));
                }
            } catch (\Exception $e) {
                // If it doesn't exist in Google anymore, just clear it locally
                if (str_contains($e->getMessage(), '404')) {
                    $task->update([
                        'google_calendar_event_id' => null,
                        'google_calendar_id' => null,
                    ]);
                    return back()->with('success', __('google.calendar_removed_success'));
                }
                Log::error('Error removing Google Calendar event: ' . $e->getMessage());
                return back()->with('error', __('google.calendar_remove_failed') . ': ' . $e->getMessage());
            }
        }

        $start = $task->scheduled_date ?: now();
        $end = $task->due_date ?: $start->copy()->addHour();

        // Ensure end is after start
        if ($end->lte($start)) {
            $end = $start->copy()->addHour();
        }

        $description = ($task->description ?: '') . "\n\n";
        $description .= "--- " . __('google.details_title') . " ---\n";
        $q = $task->getQuadrant($task);
        $description .= __('google.details_quadrant') . ": Q{$q} - " . __('tasks.quadrants.' . $q . '.label') . "\n";
        $description .= __('google.details_priority') . ": " . strtoupper(__('tasks.priorities.' . $task->priority)) . "\n";
        $description .= __('google.details_urgency') . ": " . strtoupper(__('tasks.urgencies.' . $task->urgency)) . "\n";
        $description .= __('google.details_team') . ": " . $team->name . "\n";
        $description .= __('google.details_link') . ": " . route('teams.tasks.show', [$team, $task]);

        $data = [
            'summary' => $task->title,
            'description' => trim($description),
            'start' => [
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => $user->timezone ?: config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => $user->timezone ?: config('app.timezone'),
            ],
        ];

        try {
            $eventId = $this->googleService->createEvent($data);
            if ($eventId) {
                $task->update([
                    'google_calendar_event_id' => $eventId,
                    'google_calendar_id' => 'primary',
                ]);
                return back()->with('success', __('google.calendar_export_success'));
            }
            return back()->with('error', __('google.calendar_export_failed'));
        } catch (\Exception $e) {
            Log::error('Error exporting task to Google Calendar: ' . $e->getMessage());
            
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'insufficientPermissions') || 
                str_contains($errorMsg, '403') || 
                str_contains($errorMsg, 'authentication scopes')) {
                return back()->with('error', __('google.reconnect_scopes'));
            }

            return back()->with('error', __('google.calendar_export_failed') . ': ' . $errorMsg);
        }
    }
}
