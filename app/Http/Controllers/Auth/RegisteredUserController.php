<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


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
            'terms' => ['accepted'],
            'code' => ['nullable', 'string'],
        ]);

        $isFirstUser = User::count() === 0;
        $isApproved = $isFirstUser || !\App\Models\Setting::get('require_approval', true);

        // Comprobar código de invitación de forma flexible
        $invitation = null;
        if ($request->filled('code')) {
            $code = trim($request->code);
            $invitation = \App\Models\Invitation::where(function($query) use ($code) {
                $query->where('code', $code)
                      ->orWhere('code', strtoupper($code))
                      ->orWhere('code', strtolower($code));
            })->whereNull('used_at')->first();

            if ($invitation) {
                $isApproved = true;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'locale' => $request->input('locale'),
            'timezone' => $request->input('timezone', config('app.timezone', 'UTC')),
            'is_admin' => $isFirstUser,
            'is_approved' => $isApproved,
            'invitations_left' => 5,
            'disk_quota' => config('settings.default_disk_quota', 100) * 1024 * 1024,
            'privacy_policy_accepted_at' => now(),
            'terms_accepted_at' => now(),
            'marketing_accepted_at' => $request->has('marketing') ? now() : null,
        ]);

        if ($invitation) {
            $invitation->update(['used_at' => now()]);
            if ($invitation->user_id) {
                $invitation->user()->decrement('invitations_left');
            }
        }

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
