<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comprueba tareas urgentes y envía notificaciones — cada 30 minutos
Schedule::command('tasks:check-urgent')->everyThirtyMinutes();

// Resumen Matutino con frase IA — comprobación horaria para respetar preferencias del usuario
Schedule::command('morning:summary')->hourly();

// Despierta tareas autoprogramadas que toca generar — cada día a medianoche
Schedule::command('wakeup:autoprogrammed-tasks')->dailyAt('00:00');

// Regeneración de energía progresiva — cada hora
Schedule::command('gamification:regenerate-energy')->hourly();
