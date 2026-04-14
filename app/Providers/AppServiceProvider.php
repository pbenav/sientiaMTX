<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
    }
}
