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

        $userId = $request->session()->get('login.id');
        $user = User::findOrFail($userId);

        // If the method is email, generate and send the code on load if not already sent
        if ($user->two_factor_method === 'email' && !$request->session()->has('login.email_code')) {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $request->session()->put('login.email_code', $code);
            $request->session()->put('login.email_code_expires_at', now()->addMinutes(10));

            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Tu código de acceso para iniciar sesión en Sientia Open Source Lab es: {$code}. Este código expira en 10 minutos.",
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Código de Acceso Sientia Open Source Lab - MFA');
                    }
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error sending mfa email: ' . $e->getMessage());
            }
        }

        return view('auth.two-factor', compact('user'));
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

        if ($user->two_factor_method === 'email') {
            $sessCode = $request->session()->get('login.email_code');
            $sessExpires = $request->session()->get('login.email_code_expires_at');

            if (!$sessCode || now()->greaterThan($sessExpires) || $sessCode !== $request->code) {
                SecurityLog::create([
                    'user_id' => $user->id,
                    'event' => 'auth.mfa.failed',
                    'description' => 'Intento fallido de autenticación multifactor vía EMAIL.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return back()->withErrors(['code' => __('El código introducido es incorrecto o ha caducado.')]);
            }

            $request->session()->forget(['login.email_code', 'login.email_code_expires_at']);
        } else {
            // TOTP flow
            if (!$user->two_factor_secret || !$this->totp->verifyCode($user->two_factor_secret, $request->code)) {
                SecurityLog::create([
                    'user_id' => $user->id,
                    'event' => 'auth.mfa.failed',
                    'description' => 'Intento fallido de autenticación multifactor (MFA/TOTP).',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return back()->withErrors(['code' => __('El código introducido es incorrecto o ha caducado.')]);
            }
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
            'description' => 'Inicio de sesión exitoso con autenticación multifactor vía ' . strtoupper($user->two_factor_method) . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Clear session variables
        $request->session()->forget(['login.id', 'login.remember']);

        // Prevent accidental redirection to AJAX/JSON background-polling endpoints or webhooks or downloads
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

        // Handle welcome modal preference
        if ($user->show_welcome_messages) {
            $request->session()->put('show_welcome_modal', true);
        }

        return redirect()->intended(route('dashboard'));
    }
}
