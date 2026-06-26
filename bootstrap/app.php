<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global compliance middleware: Injects X-Request-ID and audits sensitive events
        $middleware->append(\App\Http\Middleware\AuditTrailMiddleware::class);
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);

        // Outermost wrapper: catches TokenMismatchException before Laravel converts it to HttpException(419)
        $middleware->web(prepend: [
            \App\Http\Middleware\HandleSessionExpiration::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureLatestLegalConsent::class,
            \App\Http\Middleware\EnsureUserIsApproved::class,
            \App\Http\Middleware\TrackUserActivity::class,
        ]);

        $middleware->encryptCookies(except: [
            'theme',
        ]);

        $middleware->validateCsrfTokens(except: [
            '/telegram/webhook',
            '/whatsapp/webhook',
            '/passkeys/login', // Cryptographically secure, resilient to session swaps
            '/onlyoffice/callback/*',
            '/api/s2s/sync-workday',
            '/api/s2s/sync-history',
        ]);

        $middleware->trustProxies(at: '*'); // Confía en tu servidor Proxy
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
