<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Reunión
 *
 * Aparece en Kanban, Gantt y Calendar.
 * Puede sincronizarse con Google Calendar (google_calendar_event_id).
 *
 * metadata esperado:
 * {
 *   "location": "Sala A / https://meet.google.com/xxx",
 *   "modality": "presential",  // presential | remote | hybrid
 *   "duration_minutes": 60,
 *   "agenda": "Punto 1\nPunto 2",
 *   "minutes": "Actas de la reunión...",
 *   "attendee_ids": [1, 2, 3]
 * }
 */
class MeetingActivity extends Activity implements ExportableActivityInterface
{
    public const STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled'];

    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->type = 'meeting');
    }

    public function getLocation(): ?string
    {
        return $this->metadata['location'] ?? null;
    }

    public function getModality(): string
    {
        return $this->metadata['modality'] ?? 'presential';
    }

    public function getDurationMinutes(): int
    {
        return (int) ($this->metadata['duration_minutes'] ?? 60);
    }

    public function getAgenda(): ?string
    {
        return $this->metadata['agenda'] ?? null;
    }

    public function getMinutes(): ?string
    {
        return $this->metadata['minutes'] ?? null;
    }

    public function isScheduled(): bool
    {
        return $this->status_value === 'scheduled';
    }

    public function hasMinutes(): bool
    {
        return !empty($this->metadata['minutes']);
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

    public static function getSpecsSchema(): array
    {
        return [
            'location'         => 'string|nullable',
            'modality'         => 'string|nullable',
            'duration_minutes' => 'integer',
            'agenda'           => 'string|nullable',
            'minutes'          => 'string|nullable',
            'attendee_ids'     => 'array|nullable',
        ];
    }
}
