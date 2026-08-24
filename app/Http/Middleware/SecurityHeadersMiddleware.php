<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Inyectar cabeceras de seguridad HTTP robustas (OWASP & ISO 27001).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof Response) {
            // X-Frame-Options (M-08): Protege contra Clickjacking
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

            // X-Content-Type-Options (M-09): Previene el MIME-sniffing
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // Referrer-Policy (M-06): Evita fuga de URLs y tokens a terceros
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Strict-Transport-Security (H-24): HSTS obligatorio por 1 año
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

            $onlyOfficeUrl = rtrim(config('onlyoffice.url', 'https://office.sientia.com'), '/');

            // Content-Security-Policy (H-24 / L-05): Restricción de fuentes adaptada a Alpine/Tailwind/Sientia
            $csp = "default-src 'self' http://localhost:* ws://localhost:*; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://meet.jit.si https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://scaleflex.cloudimg.io {$onlyOfficeUrl}; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
                   "img-src 'self' data: blob: https://ui-avatars.com https://*.tile.openstreetmap.org; " .
                   "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com; " .
                   "media-src 'self' data: blob: https://assets.mixkit.co; " .
                   "worker-src 'self' blob:; " .
                   "connect-src 'self' data: ws://* wss://* ws://127.0.0.1:* wss://127.0.0.1:* https://meet.jit.si https://nominatim.openstreetmap.org {$onlyOfficeUrl}; " .
                   "frame-src 'self' https://meet.jit.si afirma: {$onlyOfficeUrl};";
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
