<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
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

        if ($request->has('popup')) {
            $this->googleService->getClient()->setState('popup=1');
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
            
            if (isset($token['error'])) {
                return redirect()->route('dashboard')->with('error', __('google.token_error', ['error' => $token['error']]));
            }

            $user = Auth::user();
            $user->google_token = json_encode($token);
            
            if (isset($token['refresh_token'])) {
                $user->google_refresh_token = $token['refresh_token'];
            }

            // Also fetch Google ID for reference if needed
            $oauth2 = new \Google\Service\Oauth2($this->googleService->getClient());
            $userInfo = $oauth2->userinfo->get();
            $user->google_id = $userInfo->id;
            
            $user->save();

            if ($request->has('state') && str_contains($request->state, 'popup=1')) {
                return view('google.callback-success');
            }

            return redirect()->route('dashboard')->with('success', __('google.connected_success'));
        } catch (\Exception $e) {
            Log::error('Google callback exception: ' . $e->getMessage());
            
            if ($request->has('state') && str_contains($request->state, 'popup=1')) {
                return '<html><body><script>alert("Authentication failed."); window.close();</script></body></html>';
            }

            return redirect()->route('dashboard')->with('error', __('google.auth_failed', ['error' => '']));
        }
    }

    /**
     * Show events (Calendar events) for the current user to select.
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->googleService->setTokenForUser($user)) {
            return redirect()->route('google.auth')->with('info', __('google.connect_account_first'));
        }

        $teamId = $request->input('team_id');
        if (!$teamId) {
            return back()->with('error', __('google.team_id_required'));
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
            
            $exists = \App\Models\Task::where('team_id', $teamId)
                ->where('created_by_id', $user->id)
                ->where('title', $title)
                ->where('scheduled_date', date('Y-m-d H:i:s', strtotime($due)))
                ->exists();

            return [
                'id' => 'task:' . $task->id,
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

        if (!$this->googleService->setTokenForUser($user)) {
            return redirect()->route('google.auth')->with('info', __('google.connect_account_first'));
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
                        \App\Models\Task::create([
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
                        \App\Models\Task::create([
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
                            'status' => 'pending',
                        ]);
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
    public function disconnect()
    {
        $user = auth()->user();
        $user->google_id = null;
        $user->google_token = null;
        $user->google_refresh_token = null;
        $user->save();

        return back()->with('success', __('google.disconnected_success'));
    }

    /**
     * Export a specific task to Google Tasks.
     */
    public function export(\App\Models\Team $team, \App\Models\Task $task)
    {
        $user = Auth::user();

        if (!$this->googleService->setTokenForUser($user)) {
            return redirect()->route('google.auth')->with('info', __('google.connect_account_first'));
        }

        $data = [
            'title' => $task->title,
            'notes' => $task->description ?: '',
        ];

        if ($task->scheduled_date) {
            // Google Tasks expects an RFC3339 timestamp
            $data['due'] = date('c', strtotime($task->scheduled_date));
        }

        $googleTaskId = $this->googleService->createTask($data);

        if ($googleTaskId) {
            return back()->with('success', __('google.export_success'));
        }

        return back()->with('error', __('google.export_failed'));
    }
}
