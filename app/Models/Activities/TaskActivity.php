<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Traits\HandlesEisenhowerMatrix;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Tarea / TaskActivity.
 *
 * Hereda toda la infraestructura de Activity. Es el subtipo más completo
 * con ciclo de vida, autoprogramación, gamificación y Google Sync.
 *
 * Implementa ExportableActivityInterface para exportación de specs.
 *
 * metadata esperado:
 * {
 *   "urgency": "high",
 *   "cognitive_load": 3,
 *   "is_occurrence": false,
 *   "is_out_of_skill_tree": false,
 *   "autoprogram_settings": { ... },
 *   "service_id": null,
 *   "skill_id": null,
 *   "impact_human_metric": 0
 * }
 */
class TaskActivity extends Activity implements ExportableActivityInterface
{
    use HandlesEisenhowerMatrix;

    /**
     * Boot: asigna automáticamente type='task' al crear.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->type = 'task';
        });
    }

    /**
     * Estados válidos para tareas.
     */
    public const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled', 'blocked'];

    /**
     * Verifica si la tarea está bloqueada.
     */
    public function isBlocked(): bool
    {
        return $this->status_value === 'blocked';
    }

    /**
     * Verifica si la tarea está completada.
     */
    public function isCompleted(): bool
    {
        return $this->status_value === 'completed';
    }

    /**
     * Verifica si la tarea está cancelada.
     */
    public function isCancelled(): bool
    {
        return $this->status_value === 'cancelled';
    }

    /**
     * Porcentaje de progreso. Retorna 100 si completada o cancelada.
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->isCompleted() || $this->isCancelled()) return 100;
        return (int) ($this->attributes['progress_percentage'] ?? 0);
    }

    /**
     * Sincroniza la columna de Kanban según el progreso actual.
     *
     * Progreso 100 → columna "done", 0 → "todo", otro valor → "in_progress" o "custom".
     */
    public function syncKanbanColumn(): void
    {
        $team = $this->team ?? \App\Models\Team::find($this->team_id);
        if (!$team) return;

        $progress = $this->progress_percentage;
        $expectedTypes = match(true) {
            $progress === 100 => ['done'],
            $progress === 0   => ['todo'],
            default           => ['in_progress', 'custom'],
        };

        $currentColumn = \App\Models\KanbanColumn::find($this->kanban_column_id);

        if (!$currentColumn || !in_array($currentColumn->type, $expectedTypes)) {
            $typeToAssign = $progress === 100 ? 'done' : ($progress === 0 ? 'todo' : 'in_progress');

            $column = $team->kanbanColumns()->where('type', $typeToAssign)->orderBy('order_index')->first();
            if ($column && $this->kanban_column_id !== $column->id) {
                $this->kanban_column_id = $column->id;
                $this->saveQuietly();
            }
        }
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

    /**
     * Esquema de validación para los campos de specs de tareas.
     *
     * @return array<string, string> Mapa campo → regla de validación
     */
    public static function getSpecsSchema(): array
    {
        return [
            'cognitive_load'       => 'integer',
            'is_occurrence'        => 'boolean',
            'is_out_of_skill_tree' => 'boolean',
            'autoprogram_settings' => 'array',
            'service_id'           => 'integer|nullable',
            'skill_id'             => 'integer|nullable',
            'impact_human_metric'  => 'numeric|nullable',
        ];
    }

    /**
     * Exporta los specs de la tarea como array plano.
     *
     * @return array<string, mixed>
     */
    public function exportSpecs(): array
    {
        $meta = $this->metadata ?? [];
        return [
            'cognitive_load'       => (int) ($meta['cognitive_load'] ?? $this->cognitive_load ?? 1),
            'is_occurrence'        => (bool) ($meta['is_occurrence'] ?? false),
            'is_out_of_skill_tree' => (bool) ($meta['is_out_of_skill_tree'] ?? false),
            'autoprogram_settings' => $meta['autoprogram_settings'] ?? null,
            'service_id'           => $meta['service_id'] ?? null,
            'skill_id'             => $meta['skill_id'] ?? null,
            'impact_human_metric'  => (float) ($meta['impact_human_metric'] ?? 0),
        ];
    }
}
