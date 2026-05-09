<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SecurityLog;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorLoginController extends Controller
{
    protected $totp;

    public function __construct(TotpService $totp)
    {
        $this->totp = $totp;
    }

    /**
     * Show the 2FA verification screen.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    /**
     * Verify the 2FA code and log the user in.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $userId = $request->session()->get('login.id');
        $remember = $request->session()->get('login.remember', false);

        $user = User::findOrFail($userId);

        if (!$user->two_factor_secret || !$this->totp->verifyCode($user->two_factor_secret, $request->code)) {
            SecurityLog::create([
                'user_id' => $user->id,
                'event' => 'auth.mfa.failed',
                'description' => 'Intento fallido de autenticación multifactor (MFA/2FA).',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->withErrors(['code' => __('El código introducido es incorrecto o ha caducado.')]);
        }

        // Authenticate the user
        Auth::login($user, $remember);

        // Update login stats
        $user->update([
            'last_login_at' => now(),
            'last_activity_at' => now()
        ]);

        // Log successful MFA login
        SecurityLog::create([
            'user_id' => $user->id,
            'event' => 'auth.login.mfa',
            'description' => 'Inicio de sesión exitoso con autenticación multifactor (MFA/TOTP).',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Clear session variables
        $request->session()->forget(['login.id', 'login.remember']);

        // Handle welcome modal preference
        if ($user->show_welcome_messages) {
            $request->session()->put('show_welcome_modal', true);
        }

        return redirect()->intended(route('dashboard'));
    }
}
