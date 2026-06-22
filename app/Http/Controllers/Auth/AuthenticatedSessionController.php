<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Intercept login if Multi-Factor Authentication is enabled globally and confirmed by user
        if (\App\Models\Setting::get('mfa_enabled', false) && $user->two_factor_confirmed_at) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));
            
            Auth::guard('web')->logout();

            return redirect()->route('login.two-factor');
        }

        $request->session()->regenerate();

        // Prevent accidental redirection to AJAX/JSON background-polling endpoints or webhooks
        if (session()->has('url.intended')) {
            $intended = session('url.intended');
            $blacklist = [
                'telegram', 'webhook', 'chat', 'status', 'active-network', 
                'unread-count', 'messages', 'notifications', 'gantt/data', 'api',
                'download', 'attachment'
            ];
            foreach ($blacklist as $item) {
                if (str_contains(strtolower($intended), $item)) {
                    session()->forget('url.intended');
                    break;
                }
            }
        }

        $user = Auth::user();
        $user->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
            'last_ip' => $request->ip()
        ]);

        // Show welcome message if the user prefers it
        if ($user->show_welcome_messages) {
            $request->session()->put('show_welcome_modal', true);
        }

        // Process pending invitations if a token was provided
        if ($request->has('invitation')) {
            $invitation = \App\Models\TeamInvitation::where('token', $request->invitation)
                ->where('email', $user->email)
                ->first();

            if ($invitation) {
                if (!$invitation->team->members()->where('user_id', $user->id)->exists()) {
                    $invitation->team->members()->attach($user->id, ['role_id' => $invitation->role_id]);
                }
                $invitation->delete();
                
                return redirect()->intended(route('teams.dashboard', $invitation->team))
                    ->with('success', '¡Te has unido al equipo correctamente!');
            }
        }

        $firstTeam = $user->teams()->first();

        if ($firstTeam) {
            return redirect()->intended(route('dashboard'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if ($user) {
            // Check if there are other active sessions for this user before auto-stopping timers
            $activeSessionsCount = \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->count();

            if ($activeSessionsCount === 0) {
                // Auto-stop any active time logs (workday and task) only if this was their last active session
                $user->timeLogs()->whereNull('end_at')->update(['end_at' => now()]);
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
