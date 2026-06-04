<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
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
        })->except(['webhook']);
    }

    /**
     * Muestra la vista de configuración y el QR de WhatsApp.
     */
    public function index()
    {
        // Consultamos el estado al servicio Node.js (opcional para precargar datos)
        $status = ['ready' => false, 'qr' => null];
        try {
            $response = Http::timeout(2)->get('http://localhost:3001/api/status', [
                'webhook_url' => route('whatsapp.webhook')
            ]);
            if ($response->successful()) {
                $status = $response->json();
            }
        } catch (\Exception $e) {
            // El servicio de Node.js no está corriendo
        }

        return view('whatsapp.index', compact('status'));
    }

}
