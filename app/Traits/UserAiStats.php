<?php

namespace App\Traits;

trait UserAiStats
{
    /**
     * Generates a 7-day statistical snapshot for AI analysis.
     */
    public function getAiContextStats(): array
    {
        $last7Days = now()->subDays(7);
        
        $completedTasksCount = $this->assignedTasks()
            ->where('status', 'completed')
            ->where('task_assignments.updated_at', '>=', $last7Days)
            ->count();

        $lateTasksCount = $this->assignedTasks()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        $workHours = $this->timeLogs()
            ->where('type', 'workday')
            ->where('start_at', '>=', $last7Days)
            ->get()
            ->sum(fn($log) => $log->end_at ? $log->start_at->diffInMinutes($log->end_at) : $log->start_at->diffInMinutes(now()));

        $avgWorkHoursPerDay = round(($workHours / 60) / 7, 2);

        $recentMood = $this->moodLogs()
            ->where('created_at', '>=', $last7Days)
            ->latest()
            ->first();

        return [
            'name' => $this->name,
            'experience' => $this->experience_points,
            'resilience' => $this->resilience_points,
            'energy_level_current' => $this->energy_level,
            'tasks_completed_7d' => $completedTasksCount,
            'tasks_late' => $lateTasksCount,
            'avg_work_hours_7d' => $avgWorkHoursPerDay,
            'total_kudos_received' => $this->receivedKudos()->count(),
            'last_mood_check' => $recentMood ? [
                'level' => $recentMood->energy_level,
                'label' => $recentMood->mood_label,
                'notes' => $recentMood->notes,
                'date' => $recentMood->created_at->diffForHumans()
            ] : null,
        ];
    }
}
