<?php

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

        $request->session()->regenerate();

        // Prevent accidental redirection to JSON webhooks or other non-HTML routes
        if (session()->has('url.intended') && (str_contains(session('url.intended'), 'telegram') || str_contains(session('url.intended'), 'webhook'))) {
            session()->forget('url.intended');
        }

        $user = Auth::user();

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
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
