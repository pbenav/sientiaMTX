<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Contracts\AiAssistantInterface::class, \App\Services\Ai\GeminiService::class);

        // Override Laravel Passkeys configuration generation to support Linux/Mobile QR hybrid flow
        $this->app->bind(
            \Laravel\Passkeys\Actions\GenerateRegistrationOptions::class,
            \App\Actions\Passkeys\CustomGenerateRegistrationOptions::class
        );
        $this->app->bind(
            \Laravel\Passkeys\Actions\GenerateVerificationOptions::class,
            \App\Actions\Passkeys\CustomGenerateVerificationOptions::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Survey policy
        Gate::policy(\App\Models\Survey::class, \App\Policies\SurveyPolicy::class);

        if ($this->app->environment('production', 'staging', 'local') && (str_starts_with(config('app.url') ?? '', 'https') || request()->header('X-Forwarded-Proto') === 'https')) {

            URL::forceScheme('https');
        }

        // Aplicar zona horaria global configurada en el panel de administración
        try {
            $siteTimezone = Setting::get('site_timezone', 'Europe/Madrid', true);
            if ($siteTimezone && in_array($siteTimezone, \DateTimeZone::listIdentifiers())) {
                config(['app.timezone' => $siteTimezone]);
                date_default_timezone_set($siteTimezone);
            }
        } catch (\Exception $e) {
            // Si la BD no está disponible (migraciones, etc.), usamos el valor del config
        }

        Gate::define('admin', function (\App\Models\User $user) {
            return (bool) $user->is_admin;
        });

        // Listen to Auth Events under ENS Guidelines
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function ($event) {
                \App\Models\SecurityLog::log(
                    'auth.login',
                    "Inicio de sesión correcto para el usuario: {$event->user->email}",
                    $event->user->id
                );
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            function ($event) {
                $email = $event->credentials['email'] ?? 'desconocido';
                \App\Models\SecurityLog::log(
                    'auth.failed',
                    "Intento fallido de inicio de sesión para el correo: {$email}",
                    null,
                    ['credentials' => array_keys($event->credentials)] // Do not log actual passwords!
                );
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            function ($event) {
                if ($event->user) {
                    \App\Models\SecurityLog::log(
                        'auth.logout',
                        "Cierre de sesión para el usuario: {$event->user->email}",
                        $event->user->id
                    );
                }
            }
        );

        // Define high-security password defaults under ENS guidelines
        Password::defaults(function () {
            $rule = Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols();

            // Only perform uncompromised check in production or if database/env is ready
            return app()->isProduction() 
                ? $rule->uncompromised() 
                : $rule;
        });

        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
        \App\Models\Team::observe(\App\Observers\TeamObserver::class);
        \App\Models\TaskAttachment::observe(\App\Observers\TaskAttachmentObserver::class);
    }
}
