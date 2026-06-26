<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TwoFactorAuthController extends Controller
{
    /**
     * Enable Two Factor Authentication (MFA) - Generate Secret & URI or send Email verification code.
     */
    public function enable(Request $request, \App\Services\TotpService $totp): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $method = $request->input('method', 'totp');

        $user->update([
            'two_factor_method' => $method,
        ]);

        if ($method === 'email') {
            // Generate a random 6-digit code for verification
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $request->session()->put('profile.mfa_email_code', $code);
            $request->session()->put('profile.mfa_email_expires_at', now()->addMinutes(10));

            // Send the code via email
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Tu código de verificación para activar la Autenticación de Doble Factor en Sientia Open Source Lab es: {$code}. Este código expira en 10 minutos.",
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Código de Verificación Sientia Open Source Lab - MFA');
                    }
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar el correo: ' . $e->getMessage()
                ], 500);
            }

            return response()->json([
                'success' => true,
                'method' => 'email',
                'message' => 'Código de verificación enviado a tu correo electrónico.'
            ]);
        } else {
            // TOTP flow
            if (!$user->two_factor_confirmed_at || !$user->two_factor_secret) {
                $secret = $totp->generateSecret();
                $user->update([
                    'two_factor_secret' => $secret,
                    'two_factor_confirmed_at' => null // Mark as unconfirmed
                ]);
            } else {
                $secret = $user->two_factor_secret;
            }

            $qrCodeUri = $totp->getQrCodeUri($user->email, $secret);

            return response()->json([
                'success' => true,
                'method' => 'totp',
                'secret' => $secret,
                'qr_code_uri' => $qrCodeUri,
            ]);
        }
    }

    /**
     * Confirm and finalize enabling Two Factor Authentication (MFA).
     */
    public function confirm(Request $request, \App\Services\TotpService $totp): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->two_factor_method === 'email') {
            $sessCode = $request->session()->get('profile.mfa_email_code');
            $sessExpires = $request->session()->get('profile.mfa_email_expires_at');

            if (!$sessCode || now()->greaterThan($sessExpires) || $sessCode !== $request->code) {
                return response()->json([
                    'success' => false,
                    'message' => __('El código de verificación introducido es incorrecto o ha expirado.')
                ], 422);
            }

            $request->session()->forget(['profile.mfa_email_code', 'profile.mfa_email_expires_at']);
        } else {
            // TOTP flow
            if (!$user->two_factor_secret || !$totp->verifyCode($user->two_factor_secret, $request->code)) {
                return response()->json([
                    'success' => false,
                    'message' => __('El código de verificación introducido es incorrecto.')
                ], 422);
            }
        }

        $user->update([
            'two_factor_confirmed_at' => now(),
        ]);

        \App\Models\SecurityLog::create([
            'user_id' => $user->id,
            'event' => 'auth.mfa.enabled',
            'description' => 'El usuario ha activado y verificado correctamente la Autenticación Multifactor vía ' . strtoupper($user->two_factor_method) . '.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('¡Autenticación en dos pasos activada correctamente!')
        ]);
    }

    /**
     * Disable Two Factor Authentication (MFA).
     */
    public function disable(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'password' => 'required|string|current_password',
        ]);

        $user = $request->user();

        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_method' => 'totp', // Reset to default
        ]);

        \App\Models\SecurityLog::create([
            'user_id' => $user->id,
            'event' => 'auth.mfa.disabled',
            'description' => 'El usuario ha desactivado la Autenticación Multifactor (MFA).',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('La autenticación en dos pasos ha sido desactivada.')
        ]);
    }
}
