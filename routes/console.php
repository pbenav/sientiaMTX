<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comprueba tareas urgentes y envía notificaciones — cada 30 minutos
Schedule::command('tasks:check-urgent')->everyThirtyMinutes();

// Dispara notificaciones de actividades tipo reminder según canales configurados — cada 10 minutos
Schedule::command('reminders:trigger')->everyTenMinutes();

// Comprueba tareas programadas que alcanzan su fecha de inicio — cada 10 minutos
Schedule::command('tasks:check-scheduled-wakeup')->everyTenMinutes();

// Resumen Matutino con frase IA — comprobación horaria para respetar preferencias del usuario (solo días laborables)
Schedule::command('morning:summary')->hourly()->weekdays();

// Despierta tareas autoprogramadas que toca generar — cada día a medianoche
Schedule::command('app:tasks-autoprogram-wakeup')->dailyAt('00:00');

// Regeneración de energía progresiva — cada hora
Schedule::command('gamification:regenerate-energy')->hourly();

// Fresh Start: Garantiza mínimo 80% al empezar el día — cada hora (comprueba el horario del usuario)
Schedule::command('gamification:fresh-start')->hourly();

// Saneamiento de mensajes duplicados en WhatsApp y Telegram — cada 15 minutos
Schedule::command('messages:deduplicate --apply')->everyFifteenMinutes();

// Limpieza automática de sesiones fantasma y usuarios inactivos — cada 30 minutos
Schedule::command('app:purge-ghost-sessions --threshold=60')->everyThirtyMinutes();

// Purga automática de cuentas inactivas (Aviso y Eliminación) — Cada día a las 03:00
Schedule::command('app:purge-inactive-accounts')->dailyAt('03:00');

// Recompensa de XP por la calidad de tareas vencidas — cada hora
Schedule::command('task:reward-quality')->hourly();

// Monitorización automática Sentinel — cada 15 minutos
Schedule::command('sentinel:check')->everyFifteenMinutes();

// Purga automática de mensajes de chat antiguos según preferencias de equipo — Cada día a las 04:00
Schedule::command('chat:purge-old-messages --force')->dailyAt('04:00');

// --- Métricas y Analytics Scheduled Jobs ---

// Generar snapshots de métricas cada 15 minutos
Schedule::command('metrics:snapshots')->everyFifteenMinutes();

// Generar reportes diarios a las 6:00 AM
Schedule::command('metrics:reports --daily')->dailyAt('06:00');

// Generar reporte semanal de productividad los viernes a las 3:00 PM
Schedule::command('metrics:reports --weekly --productivity')->fridays()->at('15:00');

// Generar reporte semanal de bienestar los lunes a las 9:00 AM
Schedule::command('metrics:reports --weekly --wellness')->mondays()->at('09:00');

// Generar reporte ejecutivo mensual el 1ero del mes a las 8:00 AM
Schedule::command('metrics:reports --monthly --executive')->monthly()->days(1)->at('08:00');

// Verificar alertas de métricas cada 5 minutos
Schedule::command('metrics:check-alerts')->everyFiveMinutes();

// Agregaciones semanales los domingos a las 11:00 PM
Schedule::command('metrics:snapshots --weekly')->dailyAt('23:00');

// Agregaciones mensuales el último día del mes a las 11:59 PM
Schedule::command('metrics:snapshots --monthly')->when(fn () => now()->isLastOfMonth());

