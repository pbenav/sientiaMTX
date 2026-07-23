<?php

namespace App\Traits;

/**
 * Trait ActivityTracking
 *
 * Proporciona métodos para calcular el tiempo total rastreado en actividades
 * y sus hijos, tanto en segundos como en formato legible humano.
 */
trait ActivityTracking
{
    /**
     * Calcula el total de segundos rastreados en esta actividad y sus hijos.
     * Suma los registros de tiempo propios (con end_at definido) más los de los hijos.
     */
    public function totalTrackedSeconds(): int
    {
        // Own logs
        $ownSeconds = (int) $this->timeLogs()->whereNotNull('end_at')->get()
            ->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));

        // Children logs
        $childrenSeconds = 0;
        if ($this->children()->exists()) {
             $childrenIds = $this->children()->pluck('id');
             $childrenLogs = \App\Models\TimeLog::whereIn('task_id', $childrenIds)->whereNotNull('end_at')->get();
             $childrenSeconds = (int) $childrenLogs->sum(fn($log) => max(0, $log->start_at->diffInSeconds($log->end_at, false)));
        }

        return $ownSeconds + $childrenSeconds;
    }

    /**
     * Devuelve el tiempo total rastreado en formato legible (ej: "2h 30m" o "45m").
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
     * Obtiene el tiempo total rastreado HOY por TODOS los usuarios en esta actividad y sus hijos.
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
     * Devuelve el tiempo rastreado HOY en formato legible (ej: "2h 30m" o "45m").
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
