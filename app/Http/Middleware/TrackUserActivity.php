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
            $user = Auth::user();
            $now = now();

            // Auto-logout after 60 minutes of complete inactivity
            if ($user->last_activity_at && $now->diffInMinutes($user->last_activity_at) >= 60) {
                // Auto-stop any active time logs (workday and task) on auto-logout
                $user->timeLogs()->whereNull('end_at')->update(['end_at' => $now]);

                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/')->with('warning', 'Tu sesión se ha cerrado por inactividad.');
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
