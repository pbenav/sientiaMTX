<?php

namespace App\Traits;

/**
 * Trait TaskTracking
 *
 * Proporciona métodos para calcular el tiempo rastreado en tareas
 * y sus hijos, tanto en segundos como en formato legible humano.
 *
 * Incluye tracking global (todos los usuarios) y por usuario actual.
 *
 * @mixin \App\Models\Task
 */
trait TaskTracking
{
    /**
     * Obtiene el tiempo total gastado en esta tarea y sus hijos en segundos.
     * Suma los time_logs propios (con end_at definido) más los de los hijos.
     */
    public function totalTrackedSeconds(): int
    {
        // Own logs
        $ownSeconds = (int) $this->timeLogs()->whereNotNull('end_at')->get()
            ->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));

        // Children logs (for template/parent tasks)
        $childrenSeconds = 0;
        if ($this->children()->exists()) {
             // Efficiently calculate time from all descendants
             $childrenIds = $this->children()->pluck('id');
             $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)->whereNotNull('end_at')->get();
             $childrenSeconds = (int) $childrenLogs->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));
        }

        return $ownSeconds + $childrenSeconds;
    }

    /**
     * Obtiene el tiempo total en formato legible (ej: "2h 30m").
     */
    public function totalTrackedTimeHuman(): string
    {
        $seconds = $this->totalTrackedSeconds();
        if ($seconds === 0) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Obtiene el tiempo rastreado HOY POR EL USUARIO ACTUAL en esta tarea en segundos.
     */
    public function trackedTimeTodaySeconds(): int
    {
        return (int) $this->timeLogs()
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', now()->startOfDay())
            ->whereNotNull('end_at')
            ->get()
            ->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));
    }

    /**
     * Obtiene el tiempo rastreado HOY POR EL USUARIO ACTUAL en formato legible.
     * Incluye segundos si el tiempo total es menor a 1 minuto.
     */
    public function trackedTimeTodayHuman(): string
    {
        $seconds = $this->trackedTimeTodaySeconds();
        if ($seconds === 0) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($hours == 0 && $minutes == 0) $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    /**
     * Obtiene el tiempo agregado rastreado HOY POR TODOS LOS USUARIOS
     * en esta tarea y sus hijos en segundos.
     */
    public function totalTrackedTimeTodaySeconds(): int
    {
        $own = (int) $this->timeLogs()
            ->where('created_at', '>=', now()->startOfDay())
            ->get()
            ->sum(fn($log) => $log->end_at ? max(0, $log->start_at->diffInSeconds($log->end_at, false)) : max(0, $log->start_at->diffInSeconds(now(), false)));

        $childrenSeconds = 0;
        if ($this->children()->exists()) {
            $childrenIds = $this->children()->pluck('id');
            $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)
                ->where('created_at', '>=', now()->startOfDay())
                ->get();
            $childrenSeconds = (int) $childrenLogs->sum(fn($log) => $log->end_at ? max(0, $log->start_at->diffInSeconds($log->end_at, false)) : max(0, $log->start_at->diffInSeconds(now(), false)));
        }

        return $own + $childrenSeconds;
    }

    /**
     * Obtiene el tiempo agregado rastreado HOY en formato legible.
     */
    public function totalTrackedTimeTodayHuman(): string
    {
        $seconds = $this->totalTrackedTimeTodaySeconds();
        if ($seconds === 0) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
}
