<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Traits\HandlesEisenhowerMatrix;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Tarea
 *
 * Hereda toda la infraestructura de Activity.
 * Es el subtipo más completo — con ciclo de vida, autoprogramación,
 * gamificación y Google Sync, igual que las Tasks actuales.
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

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->type = 'task';
        });
    }

    // Estados válidos para tareas
    public const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled', 'blocked'];

    public function isBlocked(): bool
    {
        return $this->status_value === 'blocked';
    }

    public function isCompleted(): bool
    {
        return $this->status_value === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status_value === 'cancelled';
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->isCompleted() || $this->isCancelled()) return 100;
        return (int) ($this->attributes['progress_percentage'] ?? 0);
    }

    /**
     * Sincroniza la columna de Kanban según el progreso actual.
     */
    public function syncKanbanColumn(): void
    {
        $team = $this->team ?? \App\Models\Team::find($this->team_id);
        if (!$team) return;

        $progress = $this->progress_percentage;
        $type = match(true) {
            $progress === 100 => 'done',
            $progress === 0   => 'todo',
            default           => 'in_progress',
        };

        $column = $team->kanbanColumns()->where('type', $type)->orderBy('order_index')->first();
        if ($column && $this->kanban_column_id !== $column->id) {
            $this->kanban_column_id = $column->id;
            $this->saveQuietly();
        }
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

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
