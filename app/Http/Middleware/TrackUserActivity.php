<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // CRITICAL FIX: Do not update user activity on AJAX heartbeat polls
            // otherwise background polling prevents the user from ever timing out naturally!
            if ($request->ajax() || $request->isXmlHttpRequest() || str_contains($request->path(), 'active-network')) {
                return $next($request);
            }

            $user = Auth::user();
            $now = now();

            // Dynamic activity limits from team settings
            $isWorking = $user->isWorking();
            $teams = $user->teams;
            $inactivityLimit = 60; // Default: 60 minutes
            $keepAlive = true;     // Default: true

            if ($teams->isNotEmpty()) {
                $limits = $teams->map(function($team) {
                    return isset($team->settings['inactivity_logout_minutes']) 
                        ? (int)$team->settings['inactivity_logout_minutes'] 
                        : 60;
                });

                if ($limits->contains(0)) {
                    $inactivityLimit = 0; // Inactivity logout disabled for at least one team
                } else {
                    $inactivityLimit = $limits->max();
                }

                $keepAlive = $teams->contains(function($team) {
                    return filter_var($team->settings['keep_alive_during_work'] ?? true, FILTER_VALIDATE_BOOLEAN);
                });
            }

            // Perform auto-logout if inactivity limit is active and exceeded
            if ($inactivityLimit > 0) {
                if ($keepAlive && $isWorking) {
                    // Keep the user active if they have active time logs (workday or task counters running)
                    $user->last_activity_at = $now;
                } elseif ($user->last_activity_at && $now->diffInMinutes($user->last_activity_at) >= $inactivityLimit) {
                    // Auto-stop any active time logs (workday and task) on auto-logout
                    $user->timeLogs()->whereNull('end_at')->update(['end_at' => $now]);

                    Auth::guard('web')->logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect('/')->with('warning', 'Tu sesión se ha cerrado por inactividad.');
                }
            }

            // Update activity timestamp
            $user->last_activity_at = $now;

            // Initialize last_login_at if it was null
            if (!$user->last_login_at) {
                $user->last_login_at = $now;
            }

            $user->save();
        }

        return $next($request);
    }
}
