<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailMiddleware
{
    /**
     * Handle an incoming request, inject X-Request-ID, and maintain compliance audit trail (HIPAA/SOC2).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Inyectar o generar X-Request-ID (Trazabilidad M-13)
        $requestId = $request->header('X-Request-ID', (string) Str::uuid());
        $request->headers->set('X-Request-ID', $requestId);

        // 2. Filtrar y sanear datos sensibles antes de registrar (Log Sanitization H-19)
        $payload = $request->except([
            'password', 'password_confirmation', 'current_password', 
            'secret', 'token', 'two_factor_code', 'recovery_code', 
            'api_key', 'authorization', '_token'
        ]);

        // 3. Rastro de Auditoría Inmutable (HIPAA 164.312(b) & SOC 2 CC7.1 / M-11)
        if ($request->method() !== 'GET' || $request->is('api/*') || $request->is('login') || $request->is('teams/*')) {
            Log::info('AUDIT_TRAIL', [
                'request_id' => $requestId,
                'user_id' => auth()->id() ?? 'guest',
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'sane_payload' => empty($payload) ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        }

        // 4. Procesar petición
        $response = $next($request);

        // 5. Adjuntar X-Request-ID en la respuesta
        if ($response instanceof Response) {
            $response->headers->set('X-Request-ID', $requestId);
        }

        return $response;
    }
}
