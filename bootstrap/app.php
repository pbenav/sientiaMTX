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
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureLatestLegalConsent::class,
        ]);

        $middleware->encryptCookies(except: [
            'theme',
        ]);

        $middleware->validateCsrfTokens(except: [
            '/telegram/webhook',
        ]);

        $middleware->trustProxies(at: '*'); // Confía en tu servidor Proxy
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('tasks:check-urgent')->hourly();
        $schedule->command('app:tasks-autoprogram-wakeup')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            return redirect()->route('home')->with('warning', 'Tu sesión ha expirado. Por favor, vuelve a intentarlo.');
        });
    })->create();
