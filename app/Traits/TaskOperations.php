<?php

namespace App\Traits;

use App\Models\Group;
use App\Models\Task;
use App\Models\Team;

trait TaskOperations
{
    /**
     * Update task priority automatically based on remaining time
     */
    public function updateAutoPriority()
    {
        if (!$this->auto_priority || !$this->due_date || $this->status === 'completed') {
            return;
        }

        $start = $this->scheduled_date ?: $this->created_at;
        $now = now();
        $due = $this->due_date;

        if ($now->gt($due)) {
            $this->priority = 'critical';
            $this->save();
            return;
        }

        $totalDuration = $start->diffInSeconds($due);
        if ($totalDuration <= 0) return;

        $remainingTime = $now->diffInSeconds($due, false);
        $percentageRemaining = ($remainingTime / $totalDuration) * 100;

        $priorities = ['low', 'medium', 'high', 'critical'];
        // We need to know the "Initial" priority to scale it.
        // But since we don't store it separately, we'll assume the scaling is absolute 
        // or relative to a baseline in metadata.
        // Let's use a simpler approach: fixed mapping by percentage for "Auto" mode.
        
        $newPriority = $this->priority;

        if ($percentageRemaining < 10) {
            $newPriority = 'critical';
        } elseif ($percentageRemaining < 25) {
            $newPriority = 'high';
        } elseif ($percentageRemaining < 50) {
            $newPriority = 'medium';
        }

        if ($newPriority !== $this->priority) {
            $this->priority = $newPriority;
            $this->save();
        }
    }

    /**
     * Get the progress percentage for template tasks
     */
    public function getProgressAttribute(): int
    {
        if (in_array($this->status, ['completed', 'cancelled'])) return 100;

        // If it has children (subtasks or instances), calculate aggregate progress
        if ($this->children()->exists()) {
            $totalCount = $this->children()->count();
            if ($totalCount === 0) return 0;

            $totalProgress = $this->children()->sum('progress_percentage');
            return (int) round($totalProgress / $totalCount);
        }

        // For individual tasks, return the manual progress percentage
        // If status is completed, it should be 100 anyway, but we return the column value
        return (int) ($this->attributes['progress_percentage'] ?? ($this->status === 'completed' ? 100 : 0));
    }

    /**
     * Synchronize the Kanban column based on current progress and status.
     */
    public function syncKanbanColumn(): void
    {
        $team = $this->team;

        // Fallback: If relationship is not loaded but team_id exists, try to find it
        if (!$team && $this->team_id) {
            $team = Team::find($this->team_id);
        }

        if (!$team) {
            return;
        }

        $currentProgress = (int)$this->progress;
        
        $expectedTypes = [];
        if ($currentProgress === 100) {
            $expectedTypes = ['done'];
        } elseif ($currentProgress === 0) {
            $expectedTypes = ['todo'];
        } else {
            $expectedTypes = ['in_progress', 'custom'];
        }

        $currentColumn = \App\Models\KanbanColumn::find($this->kanban_column_id);

        if (!$currentColumn || !in_array($currentColumn->type, $expectedTypes)) {
            $typeToAssign = $currentProgress === 100 ? 'done' : ($currentProgress === 0 ? 'todo' : 'in_progress');
            
            $defaultColumn = $team->kanbanColumns()
                ->where('type', $typeToAssign)
                ->orderBy('order_index')
                ->first();

            if ($defaultColumn && $this->kanban_column_id !== $defaultColumn->id) {
                $this->kanban_column_id = $defaultColumn->id;
                $this->saveQuietly();
            }
        }
    }

    /**
     * Iteratively generate occurrences until the next one falls outside the wakeup threshold.
     * This brings the task up to date with the current time and lead settings.
     */
    public function autoWakeup(): void
    {
        if (!$this->is_autoprogrammable) return;

        $maxIterations = 50; // Safety brake
        $iterations = 0;

        while ($this->is_autoprogrammable && $iterations < $maxIterations) {
            $settings = $this->autoprogram_settings;
            $nextAt = isset($settings['next_occurrence_at']) ? \Carbon\Carbon::parse($settings['next_occurrence_at']) : ($this->scheduled_date ? $this->scheduled_date->copy() : now());
            
            // SCENARIO 1: The task is OVERDUE or TODAY. 
            // We generate it and continue the loop to keep catching up.
            if (now()->greaterThanOrEqualTo($nextAt)) {
                $this->generateOccurrences();
                $this->refresh();
                $iterations++;
            } 
            // SCENARIO 2: The task is in the FUTURE. 
            // We only generate ONE if it's within the Lead Time, then ALWAYS STOP.
            else {
                $leadValue = (int)($settings['lead_value'] ?? 0);
                $leadUnit = $settings['lead_unit'] ?? 'days';
                
                $leadThreshold = $nextAt->copy();
                switch ($leadUnit) {
                    case 'hours': $leadThreshold->subHours($leadValue); break;
                    case 'days': $leadThreshold->subDays($leadValue); break;
                    case 'weeks': $leadThreshold->subWeeks($leadValue); break;
                    case 'months': $leadThreshold->subMonths($leadValue); break;
                }

                if (now()->greaterThanOrEqualTo($leadThreshold)) {
                    $this->generateOccurrences();
                }
                
                // Break the loop: we don't want to calculate the 2nd, 3rd, 4th future task yet.
                break; 
            }
        }
    }

    /**
     * Generate a single occurrence based on autoprogramming settings.
     */
    public function generateOccurrences(): void
    {
        $settings = $this->autoprogram_settings;
        $frequency = $settings['frequency'] ?? 'daily';
        $interval = (int)($settings['interval'] ?? 1);
        $limitType = $settings['limit_type'] ?? 'count';
        $limitValue = $settings['limit_value'] ?? 1;
        $sequential = $settings['sequential'] ?? false;
        $skipWeekends = $settings['skip_weekends'] ?? false;
        $leadValue = (int)($settings['lead_value'] ?? 7);
        $leadUnit = $settings['lead_unit'] ?? 'days';

        $lastOccurrence = $this->children()->whereNotNull('scheduled_date')->orderBy('scheduled_date', 'desc')->first();
        
        // If we already reached the limit based on count
        $occurrenceCount = $this->children()->where('metadata->is_occurrence', true)->count();
        if (!$settings) return;

        // 1. Determine the target date for the new occurrence
        $lastOccurrence = $this->children()->orderBy('scheduled_date', 'desc')->first();
        if (!$lastOccurrence) {
            $baseDate = $this->scheduled_date ? $this->scheduled_date->copy() : now();
        } else {
            $baseDate = $lastOccurrence->scheduled_date->copy();
        }

        // Calculate the actual date of the occurrence to create
        $targetDate = $this->calculateNextOccurrenceDate($baseDate, $settings, !$lastOccurrence);

        // 2. Prevent duplication
        if ($this->children()->whereDate('scheduled_date', $targetDate->toDateString())->exists()) {
            $this->updateNextOccurrenceAt($targetDate, $settings);
            return;
        }

        // 3. Create the occurrence (The child)
        $occurrence = $this->replicate(['is_autoprogrammable', 'autoprogram_settings', 'status', 'progress_percentage', 'uuid', 'google_task_id', 'google_calendar_event_id']);
        $occurrence->parent_id = $this->id;
        $occurrence->is_autoprogrammable = false;
        $occurrence->autoprogram_settings = null;
        $occurrence->status = 'pending';
        $occurrence->progress_percentage = 0;
        $occurrence->scheduled_date = $targetDate;
        
        // Mantain the same duration as the original task
        if ($this->scheduled_date && $this->due_date) {
            $duration = $this->scheduled_date->diffInMinutes($this->due_date);
            $occurrence->due_date = $targetDate->copy()->addMinutes($duration);
        }
        
        $occurrence->metadata = array_merge($occurrence->metadata ?? [], ['is_occurrence' => true]);
        $occurrence->save();

        // 4. Inherit Assignments
        foreach ($this->assignments as $assignment) {
            $occurrence->assignments()->create([
                'user_id' => $assignment->user_id,
                'group_id' => $assignment->group_id,
                'assigned_by_id' => $assignment->assigned_by_id,
            ]);
        }

        // 5. Handle Template (Distributed Mode) recursive logic
        if ($this->is_template) {
            $this->spawnInstancesForOccurrence($occurrence);
        }

        // 6. Advance the Master's pointer to the NEXT one
        $this->updateNextOccurrenceAt($targetDate, $settings);
    }

    protected function calculateNextOccurrenceDate($baseDate, $settings, $isFirst = false)
    {
        // If it's the very first one, we use the base date itself (the start of the cycle)
        if ($isFirst) return $baseDate->copy();

        $frequency = $settings['frequency'] ?? 'daily';
        $interval = (int)($settings['interval'] ?? 1);
        $nextDate = $baseDate->copy();

        switch ($frequency) {
            case 'daily':
                $nextDate->addDays($interval);
                break;
            case 'weekly':
                if (!empty($settings['days'])) {
                    $days = collect($settings['days'])->map(fn($d) => (int)$d)->sort();
                    $currentDay = $nextDate->dayOfWeekIso;
                    $nextDay = $days->first(fn($d) => $d > $currentDay);

                    if ($nextDay) {
                        $nextDate->addDays($nextDay - $currentDay);
                    } else {
                        $nextDate->addWeeks($interval);
                        $nextDate->setISODate($nextDate->year, $nextDate->weekOfYear, $days->first());
                    }
                } else {
                    $nextDate->addWeeks($interval);
                }
                break;
            case 'monthly':
                $monthlyType = $settings['monthly_type'] ?? 'date';
                if ($monthlyType === 'ordinal') {
                    $ordinal = $settings['monthly_ordinal'] ?? 'first';
                    $day = $settings['monthly_day'] ?? 'monday';
                    // Example: "first monday of +1 months"
                    $nextDate->modify("{$ordinal} {$day} of +{$interval} months");
                } else {
                    $nextDate->addMonths($interval);
                }
                break;
            case 'expression':
                $expression = $settings['expression'] ?? '';
                if (!empty($expression)) {
                    try {
                        $nextDate->modify($expression);
                    } catch (\Exception $e) {
                        // Fallback if invalid expression
                        $nextDate->addDays($interval);
                    }
                }
                break;
        }

        return $nextDate;
    }

    protected function updateNextOccurrenceAt($currentOccurrenceDate, $settings)
    {
        $nextValidDate = $this->calculateNextOccurrenceDate($currentOccurrenceDate, $settings, false);
        $settings['next_occurrence_at'] = $nextValidDate->toDateTimeString();
        $this->update(['autoprogram_settings' => $settings]);
    }

    /**
     * Helper to spawn individual instances for a specific occurrence.
     */
    protected function spawnInstancesForOccurrence(Task $occurrence): void
    {
        if (!$this->is_template) {
            $assignments = $this->assignments()->get();
            foreach ($assignments as $assignment) {
                $occurrence->assignments()->create([
                    'user_id' => $assignment->user_id,
                    'group_id' => $assignment->group_id,
                    'assigned_by_id' => $assignment->assigned_by_id,
                ]);
            }
            return;
        }

        $assignments = $this->assignments()->get();
        $userIds = collect();

        foreach ($assignments as $assignment) {
            if ($assignment->user_id) {
                $userIds->push($assignment->user_id);
            } elseif ($assignment->group_id) {
                $group = Group::find($assignment->group_id);
                if ($group) {
                    $userIds = $userIds->merge($group->users->pluck('id'));
                }
            }
        }

        $userIds->push($this->created_by_id);
        $uniqueUserIds = $userIds->unique();

        foreach ($uniqueUserIds as $userId) {
            $occurrence->children()->create([
                'team_id' => $occurrence->team_id,
                'title' => $occurrence->title,
                'description' => $occurrence->description,
                'priority' => $occurrence->priority,
                'urgency' => $occurrence->urgency,
                'status' => 'pending',
                'scheduled_date' => $occurrence->scheduled_date,
                'due_date' => $occurrence->due_date,
                'original_due_date' => $occurrence->due_date,
                'created_by_id' => $occurrence->created_by_id,
                'parent_id' => $occurrence->id,
                'is_template' => false,
                'assigned_user_id' => $userId,
                'expediente_id' => $occurrence->expediente_id,
                'visibility' => 'private',
            ]);
        }
    }

    /**
     * Check if task is blocked because its associated service is down
     */
    public function isBlockedByService(): bool
    {
        return $this->service_id && $this->service && $this->service->status === 'down';
    }

    /**
     * Get the CSS class for Frappe Gantt based on Eisenhower quadrant
     */
    public function getGanttColorClass(): string
    {
        $quadrant = $this->getQuadrant($this);
        return "gantt-q{$quadrant}";
    }

    /**
     * Recompute and cache the average quality score based on votes.
     */
    public function updateQualityCache(): void
    {
        $this->avg_quality_score = $this->ratings()->avg('score') ?: 0;
        $this->saveQuietly();
    }
}
