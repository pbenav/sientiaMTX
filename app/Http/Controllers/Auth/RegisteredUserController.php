<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $detectedLocale = $request->getPreferredLanguage(['en', 'es']) ?? config('app.locale');

        return view('auth.register', [
            'detectedLocale' => $detectedLocale
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'locale' => ['required', 'string', 'in:en,es'],
        ]);

        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'locale' => $request->locale,
            'is_admin' => $isFirstUser,
        ]);

        // Process pending invitations
        $invitations = \App\Models\TeamInvitation::where('email', $user->email)->get();
        foreach ($invitations as $invitation) {
            $invitation->team->members()->attach($user->id, ['role_id' => $invitation->role_id]);
            $invitation->delete();
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('teams.index', absolute: false));
    }
}
