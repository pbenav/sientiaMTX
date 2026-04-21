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
        // Outermost wrapper: catches TokenMismatchException before Laravel converts it to HttpException(419)
        $middleware->web(prepend: [
            \App\Http\Middleware\HandleSessionExpiration::class,
        ]);

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
        $schedule->command('morning:summary')->hourly();
        $schedule->command('gamification:regenerate-energy')->hourly();
        $schedule->command('gamification:fresh-start')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // TokenMismatchException is handled by HandleSessionExpiration middleware
        // (withExceptions callbacks receive it already converted to HttpException, so they can't redirect)
    })->create();
