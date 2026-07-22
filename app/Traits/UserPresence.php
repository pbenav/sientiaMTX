<?php

namespace App\Traits;

use App\Models\TimeLog;
use Illuminate\Support\Facades\DB;

trait UserPresence
{
    /**
     * Check if the user is currently tracking a specific task.
     */
    public function isTrackingTask(int $taskId): bool
    {
        $active = $this->activeTaskLog();
        return $active && $active->task_id === $taskId;
    }

    /**
     * Get the total seconds tracked by the user for a specific task.
     * Includes the current active session if it exists.
     */
    public function getTaskTrackingSeconds(int $taskId): int
    {
        $logs = $this->timeLogs()->where('task_id', $taskId)->get();
        
        return (int) $logs->sum(function($log) {
            if ($log->end_at) {
                return $log->start_at->diffInSeconds($log->end_at);
            }
            // If it's the active log, include seconds until now
            return $log->start_at->diffInSeconds(now());
        });
    }

    public function activeWorkdayLog(): ?TimeLog
    {
        $today = now()->startOfDay();
        if ($this->relationLoaded('timeLogs')) {
            return $this->timeLogs->where('type', 'workday')
                ->whereNull('end_at')
                ->where('start_at', '>=', $today)
                ->first();
        }
        return $this->timeLogs()
            ->where('type', 'workday')
            ->whereNull('end_at')
            ->where('start_at', '>=', $today)
            ->first();
    }

    public function activeTaskLog(): ?TimeLog
    {
        $today = now()->startOfDay();
        if ($this->relationLoaded('timeLogs')) {
            return $this->timeLogs->where('type', 'task')
                ->whereNull('end_at')
                ->where('start_at', '>=', $today)
                ->first();
        }
        return $this->timeLogs()
            ->where('type', 'task')
            ->whereNull('end_at')
            ->where('start_at', '>=', $today)
            ->first();
    }

    /**
     * Determine if the user is currently online based on session activity.
     */
    public function isOnline(): bool
    {
        return DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>', now()->subMinutes(15)->getTimestamp())
            ->exists();
    }

    /**
     * Determine if the user has any active work or task counter.
     */
    public function isWorking(): bool
    {
        return $this->timeLogs()
            ->whereIn('type', ['workday', 'task'])
            ->whereNull('end_at')
            ->where('start_at', '>=', now()->startOfDay())
            ->exists();
    }

    /**
     * Get user status info for UI indicators.
     */
    public function getStatusInfo(): array
    {
        $lastActivity = $this->last_login_at ? $this->last_activity_at : null;
        $isWorking = $this->last_login_at ? $this->isWorking() : false;
        $isOnline = $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(15));
        $isSleeping = !$isOnline && $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(60));

        if ($isWorking && $this->last_login_at) {
            return [
                'status' => 'working',
                'label' => __('En labor'),
                'color' => 'rose-500',
                'animate' => 'animate-pulse',
                'dot_class' => 'bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.6)]'
            ];
        }

        if ($isOnline) {
            return [
                'status' => 'online',
                'label' => __('Activo'),
                'color' => 'emerald-500',
                'animate' => 'animate-ping',
                'dot_class' => 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.4)]'
            ];
        }

        if ($isSleeping) {
            return [
                'status' => 'sleeping',
                'label' => __('Dormido'),
                'color' => 'amber-500',
                'animate' => 'animate-pulse',
                'dot_class' => 'bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]'
            ];
        }

        return [
            'status' => 'offline',
            'label' => __('Desconectado'),
            'color' => 'gray-400',
            'animate' => '',
            'dot_class' => 'bg-gray-300 dark:bg-gray-700'
        ];
    }
}
