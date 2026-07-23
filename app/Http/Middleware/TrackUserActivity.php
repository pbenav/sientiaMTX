<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Gestiona el seguimiento de actividad del usuario, detecta solicitudes en segundo
     * plano (polls, heartbeats, widget refreshes), controla la expiración por inactividad
     * y detiene los temporizadores de trabajo si corresponde.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $now = now();

            // Detect background automated requests (polls, heartbeats, widget refreshes)
            // We explicitly check keywords because modern fetch() API calls often don't send 'X-Requested-With' headers.
            $backgroundKeywords = [
                'active-network',
                'chat/check',
                'comms/heartbeat',
                'comms/presence',
                'telegram-chat',
                'whatsapp-chat',
                'notifications/unread-count',
                'time-logs/status',
                'whatsapp/status',
                'whatsapp/personal-status'
            ];

            $isPollRequest = $request->ajax() || 
                             $request->isXmlHttpRequest() || 
                             $request->headers->get('X-Requested-With') === 'XMLHttpRequest' ||
                             \Illuminate\Support\Str::contains($request->path(), $backgroundKeywords);

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
                $hasTimedOut = $user->last_activity_at && $now->diffInMinutes($user->last_activity_at) >= $inactivityLimit;
                $shouldKeepAlive = $keepAlive && $isWorking;

                if (!$shouldKeepAlive && $hasTimedOut) {
                    // Check if there are other active sessions for this user before auto-stopping timers
                    $activeSessionsCount = \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('user_id', $user->id)
                        ->where('id', '!=', $request->session()->getId())
                        ->where('last_activity', '>=', now()->subMinutes($inactivityLimit)->getTimestamp())
                        ->count();

                    if ($activeSessionsCount === 0) {
                        // Al cerrarse por inactividad la última sesión activa, detenemos todos los contadores en MTX y CTH
                        $user->timeLogs()->whereNull('end_at')->update(['end_at' => $now]);

                        if ($user->sync_with_cth) {
                            \App\Jobs\SyncWorkdayWithCth::dispatch($user, 'stop');
                        }
                    }

                    Auth::guard('web')->logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    // If this is an automated poll, return explicit 401 to signal front-end stop
                    if ($isPollRequest) {
                        return response()->json(['message' => 'Session closed due to inactivity'], 401);
                    }

                    return redirect('/')->with('warning', 'Tu sesión se ha cerrado por inactividad.');
                }
            }

            // CRITICAL LOGIC: 
            // 1. Update timestamp ALWAYS on non-poll requests (explicit human activity)
            // 2. Update timestamp on Polls ONLY if currently actively working (KeepAlive protocol)
            $shouldExtendLife = !$isPollRequest || ($keepAlive && $isWorking);

            if ($shouldExtendLife) {
                $user->last_activity_at = $now;
                $user->last_ip = $request->ip();

                // AUTO-RESET DE AVISO DE PURGA: Si el usuario accede y tenía un aviso de eliminación pendiente, lo cancelamos.
                if ($user->inactive_warning_sent_at) {
                    $user->inactive_warning_sent_at = null;
                    
                    if (!$isPollRequest && $request->hasSession()) {
                        $request->session()->flash('success', '¡Qué alegría volver a verte! Tu cuenta ha sido reactivada con éxito. Gracias por seguir con nosotros.');
                    }
                }

                // Initialize last_login_at if it was null
                if (!$user->last_login_at) {
                    $user->last_login_at = $now;
                }

                $user->save();
            }
        }

        return $next($request);
    }
}
