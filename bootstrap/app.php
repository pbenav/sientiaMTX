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

        $trusted = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(at: $trusted === '*' ? '*' : array_map('trim', explode(',', $trusted)));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Redirección elegante para rutas legacy /tasks/{id} que ya no existen
        // Las tareas han sido migradas al modelo unificado Activity
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if (str_contains($request->path(), '/tasks/')) {
                // Intentar encontrar el equipo en la URL para redirigir correctamente
                preg_match('#teams/(\d+)#', $request->path(), $matches);
                $teamId = $matches[1] ?? null;

                $redirectUrl = $teamId
                    ? route('teams.activities.index', $teamId)
                    : route('dashboard');

                return redirect($redirectUrl)
                    ->with('warning', __('Esta tarea ha sido migrada o eliminada. Usa el listado de actividades para encontrarla.'));
            }
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'SQLSTATE[HY000] [2002]')) {
                return response()->view('errors.database', [], 500);
            }
        });

        $exceptions->render(function (\PDOException $e, \Illuminate\Http\Request $request) {
            if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'SQLSTATE[HY000] [2002]')) {
                return response()->view('errors.database', [], 500);
            }
        });
    })->create();
