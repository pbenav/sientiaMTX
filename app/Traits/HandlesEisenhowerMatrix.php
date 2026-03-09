<?php

namespace App\Traits;

use App\Models\Task;

trait HandlesEisenhowerMatrix
{
    /**
     * Determine which quadrant a task belongs to based on priority and urgency.
     * 
     * @param Task $task
     * @return int
     */
    public function getQuadrant(Task $task): int
    {
        $isPriority = in_array($task->priority, ['high', 'critical']);
        $isUrgent = in_array($task->urgency, ['high', 'critical']);

        if ($isPriority && $isUrgent) {
            return 1; // Do First (Important + Urgent)
        }
        
        if ($isPriority && !$isUrgent) {
            return 2; // Schedule (Important + Not Urgent)
        }
        
        if (!$isPriority && $isUrgent) {
            return 3; // Delegate (Not Important + Urgent)
        }
        
        return 4; // Eliminate (Not Important + Not Urgent)
    }

    /**
     * Get the quadrant metadata (label, description, color, etc.)
     * 
     * @param int $quadrant
     * @return array
     */
    public function getQuadrantMetadata(int $quadrant): array
    {
        return [
            1 => [
                'label' => __('tasks.quadrants.1.label'),
                'description' => __('tasks.quadrants.1.description'),
                'tip' => __('tasks.quadrants.1.tip'),
                'color' => 'red',
            ],
            2 => [
                'label' => __('tasks.quadrants.2.label'),
                'description' => __('tasks.quadrants.2.description'),
                'tip' => __('tasks.quadrants.2.tip'),
                'color' => 'blue',
            ],
            3 => [
                'label' => __('tasks.quadrants.3.label'),
                'description' => __('tasks.quadrants.3.description'),
                'tip' => __('tasks.quadrants.3.tip'),
                'color' => 'amber',
            ],
            4 => [
                'label' => __('tasks.quadrants.4.label'),
                'description' => __('tasks.quadrants.4.description'),
                'tip' => __('tasks.quadrants.4.tip'),
                'color' => 'gray',
            ],
        ][$quadrant] ?? [];
    }
}
