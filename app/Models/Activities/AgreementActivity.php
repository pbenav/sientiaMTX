<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Decisión
 *
 * Registro formal de una decisión tomada por el equipo.
 *
 * metadata esperado:
 * {
 *   "rationale": "Motivo de la decisión",
 *   "alternatives": ["Opción A", "Opción B"],
 *   "impact": "high",          // low | medium | high
 *   "decided_at": "2026-06-26",
 *   "decided_by_ids": [1, 2]   // IDs de usuarios decisores
 * }
 */
class AgreementActivity extends Activity implements ExportableActivityInterface
{
    public const STATUSES = ['proposed', 'approved', 'rejected', 'deferred', 'superseded'];

    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->type = 'agreement');
    }

    public function getRationale(): ?string
    {
        return $this->metadata['rationale'] ?? null;
    }

    public function getAlternatives(): array
    {
        return $this->metadata['alternatives'] ?? [];
    }

    public function getImpact(): string
    {
        return $this->metadata['impact'] ?? 'medium';
    }

    public function isApproved(): bool
    {
        return $this->status_value === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status_value === 'rejected';
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

    public static function getSpecsSchema(): array
    {
        return [
            'rationale'      => 'string|nullable',
            'alternatives'   => 'array|nullable',
            'impact'         => 'string|nullable',
            'decided_at'     => 'string|nullable',
            'decided_by_ids' => 'array|nullable',
        ];
    }
}
