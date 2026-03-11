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
    public function redirect()
    {
        if (!$this->googleService->isConfigured()) {
            return redirect()->route('dashboard')->with('error', __('Google integration is not configured by the administrator.'));
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

            return redirect()->route('dashboard')->with('success', 'Google account connected successfully.');
        } catch (\Exception $e) {
            Log::error('Google callback exception: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'An error occurred during Google authentication.');
        }
    }

    /**
     * Sync objects (Calendar events) for the current user.
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->googleService->setTokenForUser($user)) {
            return redirect()->route('google.auth')->with('info', 'Please connect your Google account first.');
        }

        $events = $this->googleService->listEvents(20);
        $syncCount = 0;
        
        // Visibility from request or default to user preference (we'll use 'private' by default for now)
        $visibility = $request->input('visibility', 'private');
        $teamId = $request->input('team_id');

        if (!$teamId) {
            return back()->with('error', 'Team ID is required for synchronization.');
        }

        foreach ($events as $event) {
            // Check if task already exists via metadata or title/date
            // Basic logic: title + start date
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
                    'priority' => 'low', // Default
                    'urgency' => 'low',   // Default
                    'status' => 'pending',
                ]);
                $syncCount++;
            }
        }

        return back()->with('success', "Synced $syncCount new tasks from your Google Calendar.");
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
