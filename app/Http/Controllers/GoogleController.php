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
            return redirect()->route('dashboard')->with('error', __('Google integration is not configured by the administrator.'));
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
            return redirect()->route('dashboard')->with('error', 'Google authentication failed: ' . $request->error);
        }

        if (!$request->has('code')) {
            return redirect()->route('dashboard')->with('error', 'Invalid Google callback.');
        }

        try {
            $token = $this->googleService->getClient()->fetchAccessTokenWithAuthCode($request->code);
            
            if (isset($token['error'])) {
                return redirect()->route('dashboard')->with('error', 'Error fetching token: ' . $token['error']);
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

            return redirect()->route('dashboard')->with('success', 'Google account connected successfully.');
        } catch (\Exception $e) {
            Log::error('Google callback exception: ' . $e->getMessage());
            
            if ($request->has('state') && str_contains($request->state, 'popup=1')) {
                return '<html><body><script>alert("Authentication failed."); window.close();</script></body></html>';
            }

            return redirect()->route('dashboard')->with('error', 'An error occurred during Google authentication.');
        }
    }

    /**
     * Show events (Calendar events) for the current user to select.
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->googleService->setTokenForUser($user)) {
            return redirect()->route('google.auth')->with('info', 'Please connect your Google account first.');
        }

        $teamId = $request->input('team_id');
        if (!$teamId) {
            return back()->with('error', 'Team ID is required for synchronization.');
        }

        $team = \App\Models\Team::findOrFail($teamId);
        $events = $this->googleService->listEvents(30);

        // Pre-check for existing tasks to help the user identify duplicates
        $eventsData = collect($events)->map(function($event) use ($teamId, $user) {
            $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
            $title = $event->getSummary();
            
            $exists = \App\Models\Task::where('team_id', $teamId)
                ->where('created_by_id', $user->id)
                ->where('title', $title)
                ->where('scheduled_date', date('Y-m-d H:i:s', strtotime($start)))
                ->exists();

            return [
                'id' => $event->id,
                'title' => $title,
                'description' => $event->getDescription() ?: '',
                'start' => $start,
                'end' => $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate(),
                'exists' => $exists,
            ];
        });

        return view('google.select-tasks', [
            'events' => $eventsData,
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
            return back()->with('error', 'No tasks selected for import.');
        }

        if (!$this->googleService->setTokenForUser($user)) {
            return redirect()->route('google.auth')->with('info', 'Please connect your Google account first.');
        }

        // Fetch events again to process (or we could have passed all data in the request if small)
        // For reliability, we fetch them again
        $allEvents = collect($this->googleService->listEvents(50));
        $syncCount = 0;

        foreach ($allEvents as $event) {
            if (in_array($event->id, $selectedEventIds)) {
                $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
                $title = $event->getSummary();

                // Double check existence just in case
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

        return redirect()->route('teams.dashboard', $teamId)
            ->with('success', "Successfully imported $syncCount tasks from Google Calendar.");
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

        return back()->with('success', __('Google account disconnected successfully.'));
    }
}
