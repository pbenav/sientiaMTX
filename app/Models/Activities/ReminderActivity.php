<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Recordatorio
 *
 * Aparece en Matrix y Gantt. Se dispara según la configuración de notificación.
 * Puede tener múltiples canales de notificación.
 *
 * metadata esperado:
 * {
 *   "channels": ["email", "whatsapp", "telegram", "push"],
 *   "repeat": false,
 *   "repeat_interval": null,   // daily | weekly | monthly
 *   "notified_at": null,
 *   "snooze_until": null,
 *   "notify_before_minutes": null,  // Notificar X minutos antes de due_date
 *   "notify_at_hour": null          // Notificar a una hora exacta (formato "HH:MM" o "HH:MM:SS")
 * }
 *
 * Reglas de notificación:
 * - Si notify_before_minutes está definido: notificar due_date - X minutos
 * - Si notify_at_hour está definido: notificar a esa hora en la fecha de due_date
 * - Si no se define ninguno: notificar en la due_date exacta
 */
class ReminderActivity extends Activity implements ExportableActivityInterface
{
    public const STATUSES = ['pending', 'triggered', 'dismissed', 'snoozed'];

    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->type = 'reminder');
    }

    public function getChannels(): array
    {
        return $this->metadata['channels'] ?? ['email'];
    }

    public function isRepeat(): bool
    {
        return (bool) ($this->metadata['repeat'] ?? false);
    }

    public function hasBeenTriggered(): bool
    {
        return $this->status_value === 'triggered';
    }

    public function isDismissed(): bool
    {
        return $this->status_value === 'dismissed';
    }

    public function isSnoozed(): bool
    {
        return $this->status_value === 'snoozed';
    }

    public function isSnoozedUntil(): ?\Carbon\Carbon
    {
        $snooze = $this->metadata['snooze_until'] ?? null;
        return $snooze ? \Carbon\Carbon::parse($snooze) : null;
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

    public static function getSpecsSchema(): array
    {
        return [
            'channels'              => 'array|nullable',
            'repeat'                => 'boolean|nullable',
            'repeat_interval'       => 'string|nullable',
            'notified_at'           => 'string|nullable',
            'snooze_until'          => 'string|nullable',
            'notify_before_minutes' => 'integer|nullable',
            'notify_at_hour'        => 'string|nullable',
        ];
    }
}
