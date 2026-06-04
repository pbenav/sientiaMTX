<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!config('services.whatsapp.enabled', true)) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => 'El módulo de WhatsApp está globalmente desactivado.'], 403);
                }
                abort(403, 'El módulo de WhatsApp está globalmente desactivado.');
            }
            return $next($request);
        });
    }

    /**
     * Reinicia el cliente de WhatsApp en Node.js para generar un nuevo QR
     */
    public function restart(Request $request)
    {
        $user = auth()->user();
        $notifSettings = $user->notification_settings ?? $user->defaultNotificationSettings();
        if (!($notifSettings['whatsapp'] ?? false)) {
            return response()->json(['success' => false, 'error' => 'Módulo de WhatsApp desactivado.'], 403);
        }

        try {
            $response = Http::timeout(10)->post('http://localhost:3001/api/restart');
            return response()->json(['success' => true, 'message' => 'Reiniciando cliente']);
        } catch (\Exception $e) {
            Log::error('Error reiniciando WhatsApp: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy de estado global de WhatsApp Web Bridge
     */
    public function status(Request $request)
    {
        try {
            $response = Http::timeout(3)->get('http://localhost:3001/api/status');
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['ready' => false, 'qr' => ''], 502);
        } catch (\Exception $e) {
            return response()->json(['ready' => false, 'qr' => '', 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Proxy de estado de WhatsApp Personal
     */
    public function personalStatus(Request $request)
    {
        try {
            $session = 'user_' . auth()->id();
            $init = $request->get('init') === 'true' ? '&init=true' : '';
            $response = Http::timeout(3)->get('http://localhost:3001/api/status?session=' . $session . $init);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['ready' => false, 'qr' => ''], 502);
        } catch (\Exception $e) {
            return response()->json(['ready' => false, 'qr' => '', 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Desvincula o reinicia la sesión de WhatsApp Personal
     */
    public function personalRestart(Request $request)
    {
        try {
            $session = 'user_' . auth()->id();
            $response = Http::timeout(10)->post('http://localhost:3001/api/restart', [
                'session' => $session
            ]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error desvinculando WhatsApp Personal: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy de estado de WhatsApp de Equipo
     */
    public function teamStatus(Request $request)
    {
        try {
            $team = \App\Models\Team::findOrFail($request->get('team_id'));
            if (!$team->members->contains(auth()->id()) && !auth()->user()->is_admin) {
                abort(403);
            }
            $session = 'team_' . ($team->slug ?: $team->id);
            $init = $request->get('init') === 'true' ? '&init=true' : '';
            $response = Http::timeout(3)->get('http://localhost:3001/api/status?session=' . $session . $init);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['ready' => false, 'qr' => ''], 502);
        } catch (\Exception $e) {
            return response()->json(['ready' => false, 'qr' => '', 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Desvincula o reinicia la sesión de WhatsApp de Equipo
     */
    public function teamRestart(Request $request)
    {
        try {
            $team = \App\Models\Team::findOrFail($request->get('team_id'));
            if ($team->user_id !== auth()->id() && !$team->isCoordinator(auth()->user()) && !auth()->user()->is_admin) {
                abort(403);
            }
            $session = 'team_' . ($team->slug ?: $team->id);
            $response = Http::timeout(10)->post('http://localhost:3001/api/restart', [
                'session' => $session
            ]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error desvinculando WhatsApp de Equipo: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
