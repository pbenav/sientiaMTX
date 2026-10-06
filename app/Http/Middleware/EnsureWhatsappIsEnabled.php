<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWhatsappIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('services.whatsapp.enabled', true)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'El módulo de WhatsApp está globalmente desactivado.'], 403);
            }
            abort(403, 'El módulo de WhatsApp está globalmente desactivado.');
        }

        return $next($request);
    }
}

