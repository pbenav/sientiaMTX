<?php

namespace App\Traits;

use App\Models\Task;
use App\Models\Activity;

/**
 * Trait HandlesEisenhowerMatrix
 *
 * Determina el cuadrante de la Matriz de Eisenhower para tareas/actividades
 * basado en su prioridad y urgencia, y proporciona metadatos de UI por cuadrante.
 *
 * Cuadrantes:
 * - 1 (Do First): Importante + Urgente → rojo
 * - 2 (Schedule): Importante + No Urgente → azul
 * - 3 (Delegate): No Importante + Urgente → ámbar
 * - 4 (Eliminate): No Importante + No Urgente → gris
 *
 * @mixin \App\Models\Task|\App\Models\Activity
 */
trait HandlesEisenhowerMatrix
{
    /**
     * Determina a qué cuadrante pertenece una tarea/actividad según prioridad y urgencia.
     *
     * Reglas:
     * - priority high/critical = Importante
     * - urgency high/critical = Urgente
     *
     * @param Task|Activity $task La tarea o actividad a evaluar
     * @return int El cuadrante (1, 2, 3, o 4)
     */
    public function getQuadrant(Task|Activity $task): int
    {
        $priority = $task->priority;
        $urgency = $task->urgency ?? data_get($task->metadata, 'urgency', 'medium');

        if (is_object($priority) && enum_exists(get_class($priority))) {
            $priority = $priority->value;
        }

        // Strictly match the Controller mapping: 
        // Important = high/critical, Not Important = low/medium
        // Urgent = high/critical, Not Urgent = low/medium
        $isPriority = in_array($priority, ['high', 'critical']);
        $isUrgent = in_array($urgency, ['high', 'critical']);

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
     * Obtiene los metadatos del cuadrante (etiqueta, descripción, color, etc.).
     * Las etiquetas y descripciones se cargan desde archivos de traducción.
     *
     * @param int $quadrant El cuadrante (1-4)
     * @return array ['label', 'description', 'tip', 'color']
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
