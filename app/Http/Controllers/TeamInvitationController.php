<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamInvitationController extends Controller
{
    /**
     * Handle the team invitation acceptance.
     */
    public function accept(Request $request, $token)
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        // Check if user is already logged in
        if (Auth::check()) {
            $user = Auth::user();

            // Verify if the email matches
            if ($user->email !== $invitation->email) {
                return redirect()->route('dashboard')->with('error', 'Esta invitación es para otra cuenta de correo.');
            }

            // Attach user to team
            if (!$invitation->team->members()->where('user_id', $user->id)->exists()) {
                $invitation->team->members()->attach($user->id, ['role_id' => $invitation->role_id]);
            }

            $invitation->delete();

            return redirect()->route('teams.dashboard', $invitation->team)
                ->with('success', '¡Te has unido al equipo correctamente!');
        }

        // If not logged in, check if user exists
        $userExists = User::where('email', $invitation->email)->exists();

        if ($userExists) {
            // Redirect to login with invitation info
            return redirect()->route('login', ['email' => $invitation->email, 'invitation' => $token])
                ->with('status', 'Por favor, inicia sesión para aceptar la invitación al equipo.');
        }

        // If user doesn't exist, redirect to register
        return redirect()->route('register', ['email' => $invitation->email, 'invitation' => $token]);
    }
}
