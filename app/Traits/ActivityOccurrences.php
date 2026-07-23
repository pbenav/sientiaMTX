<?php

namespace App\Traits;

use App\Models\Activity;
use App\Models\Group;
use Carbon\Carbon;

/**
 * Trait ActivityOccurrences
 *
 * Maneja la generación automática de ocurrencias (instancias) para actividades
 * autoprogramables (recurring tasks, meetings, reminders, etc.).
 *
 * Incluye:
 * - autoWakeup: sincroniza las ocurrencias hasta la fecha actual
 * - generateOccurrences: crea una nueva ocurrencia basada en settings
 * - calculateNextOccurrenceDate: calcula la próxima fecha según frecuencia
 * - spawnInstancesForOccurrence: crea instancias distribuidas para templates
 *
 * @mixin \App\Models\Activity
 */
trait ActivityOccurrences
{
    /**
     * Iterativamente genera ocurrencias hasta que la próxima cae fuera del umbral de wakeup.
     * Sincroniza las ocurrencias con la fecha/hora actual y la configuración de lead time.
     */
    public function autoWakeup(): void
    {
        $isAutoprogrammable = data_get($this->metadata, 'is_autoprogrammable', false);
        if (!$isAutoprogrammable) return;

        $maxIterations = 50; // Safety brake
        $iterations = 0;

        while (data_get($this->metadata, 'is_autoprogrammable', false) && $iterations < $maxIterations) {
            $settings = data_get($this->metadata, 'autoprogram_settings', []);
            $nextAt = isset($settings['next_occurrence_at']) ? Carbon::parse($settings['next_occurrence_at']) : ($this->scheduled_date ? $this->scheduled_date->copy() : now());
            
            // SCENARIO 1: The activity is OVERDUE or TODAY. 
            // We generate it and continue the loop to keep catching up.
            if (now()->greaterThanOrEqualTo($nextAt)) {
                $this->generateOccurrences();
                $this->refresh();
                $iterations++;
            } 
            // SCENARIO 2: The activity is in the FUTURE. 
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
     * Genera una única ocurrencia basada en la configuración de autoprogramación.
     * Crea un hijo con la fecha calculada, hereda asignaciones, y si es template,
     * genera instancias distribuidas para cada usuario asignado.
     */
    public function generateOccurrences(): void
    {
        $settings = data_get($this->metadata, 'autoprogram_settings', []);
        if (empty($settings)) return;

        // If we already reached the limit based on count
        // $occurrenceCount = $this->children()->where('metadata->is_occurrence', true)->count(); // Can be used later for limits
        
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
        $occurrence = $this->replicate(['status', 'progress_percentage', 'uuid', 'google_task_id', 'google_calendar_event_id']);
        $occurrence->parent_id = $this->id;
        $occurrence->status = ['value' => 'pending'];
        $occurrence->progress_percentage = 0;
        $occurrence->scheduled_date = $targetDate;
        
        // Mantain the same duration as the original activity
        if ($this->scheduled_date && $this->due_date) {
            $duration = $this->scheduled_date->diffInMinutes($this->due_date);
            $occurrence->due_date = $targetDate->copy()->addMinutes($duration);
        }
        
        // Modify metadata to indicate this is an occurrence and disable its own autoprogramming
        $metadata = $occurrence->metadata ?? [];
        $metadata['is_occurrence'] = true;
        $metadata['is_autoprogrammable'] = false;
        unset($metadata['autoprogram_settings']);
        
        $occurrence->metadata = $metadata;
        $occurrence->save();

        // 4. Inherit Assignments
        $activityService = app(\App\Services\ActivityService::class);
        $userIds = $this->assignedTo()->pluck('users.id')->toArray();
        $groupIds = $this->assignedGroups()->pluck('groups.id')->toArray();
        
        if (!empty($userIds) || !empty($groupIds)) {
            $activityService->syncAssignments($occurrence, $userIds, $groupIds);
        }

        // 5. Handle Template (Distributed Mode) recursive logic
        if ($this->is_template) {
            $this->spawnInstancesForOccurrence($occurrence);
        }

        // 6. Advance the Master's pointer to the NEXT one
        $this->updateNextOccurrenceAt($targetDate, $settings);
    }

    /**
     * Calcula la fecha de la próxima ocurrencia basada en la frecuencia, intervalo y configuración mensual.
     * Soporta: daily, weekly, monthly (date/ordinal), y expresión cron.
     *
     * @param Carbon $baseDate Fecha base para el cálculo
     * @param array $settings Configuración de autoprogramación
     * @param bool $isFirst Si es la primera ocurrencia del ciclo
     * @return Carbon La fecha calculada
     */
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

    /**
     * Actualiza el puntero next_occurrence_at en metadata con la siguiente fecha válida.
     * Se usa para avanzar el ciclo de autoprogramación después de generar una ocurrencia.
     */
    protected function updateNextOccurrenceAt($currentOccurrenceDate, $settings)
    {
        $nextValidDate = $this->calculateNextOccurrenceDate($currentOccurrenceDate, $settings, false);
        $settings['next_occurrence_at'] = $nextValidDate->toDateTimeString();
        
        $metadata = $this->metadata ?? [];
        $metadata['autoprogram_settings'] = $settings;
        
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Genera instancias individuales para cada usuario asignado cuando una ocurrencia
     * proviene de una plantilla maestra (Distributed Mode).
     * Distribuye la ocurrencia a todos los usuarios del equipo asignados.
     */
    protected function spawnInstancesForOccurrence(Activity $occurrence): void
    {
        if (!$this->is_template) {
            return;
        }

        $activityService = app(\App\Services\ActivityService::class);
        $userIds = $this->assignedTo()->pluck('users.id')->toArray();
        $groupIds = $this->assignedGroups()->pluck('groups.id')->toArray();
        
        $allUserIds = collect($userIds);
        
        foreach ($groupIds as $groupId) {
            $group = Group::find($groupId);
            if ($group) {
                $allUserIds = $allUserIds->merge($group->users->pluck('id'));
            }
        }
        
        $uniqueUserIds = $allUserIds->unique();
        
        // Use the ActivityService to distribute the instances correctly for the occurrence
        $activityService->syncDistributedInstances($occurrence, $uniqueUserIds->toArray());
    }
}
