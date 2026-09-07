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

            // Strict-Transport-Security (H-24): Solo en conexiones HTTPS de producción, nunca en localhost/desarrollo local ni IPs
            $host = $request->getHost();
            $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
            $isLocal = $isIp || in_array($host, ['localhost', '127.0.0.1', '::1']) || str_ends_with($host, '.test') || str_ends_with($host, '.local') || app()->environment('local');
            if ($request->isSecure() && !$isLocal) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            } else {
                $response->headers->remove('Strict-Transport-Security');
            }

            $onlyOfficeUrl = rtrim(config('onlyoffice.url', 'https://office.sientia.com'), '/');
            $appUrl = rtrim(config('app.url', 'https://mtx.sientia.com'), '/');
            $appHost = parse_url($appUrl, PHP_URL_HOST);
            $appOrigin = ($appHost && !in_array($appHost, ['localhost', '127.0.0.1', '::1'])) ? "https://{$appHost}" : '';

            // Content-Security-Policy (H-24 / L-05): Restricción de fuentes adaptada a Alpine/Tailwind/Sientia
            $csp = "default-src 'self' http://localhost:* ws://localhost:*; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$appOrigin} https://cdn.tailwindcss.com https://meet.jit.si https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://scaleflex.cloudimg.io {$onlyOfficeUrl}; " .
                   "style-src 'self' 'unsafe-inline' {$appOrigin} https://fonts.bunny.net https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net; " .
                   "img-src 'self' data: blob: {$appOrigin} https://ui-avatars.com https://*.tile.openstreetmap.org https://unpkg.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                   "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com; " .
                   "media-src 'self' data: blob: https://assets.mixkit.co; " .
                   "worker-src 'self' blob:; " .
                   "connect-src 'self' data: ws://* wss://* ws://127.0.0.1:* wss://127.0.0.1:* {$appOrigin} https://meet.jit.si https://nominatim.openstreetmap.org https://unpkg.com {$onlyOfficeUrl}; " .
                   "frame-src 'self' https://meet.jit.si afirma: {$onlyOfficeUrl};";
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
