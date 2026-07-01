<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Nota
 *
 * metadata esperado:
 * {
 *   "format": "markdown",   // markdown | html | plain
 *   "pinned": false
 * }
 */
class NoteActivity extends Activity implements ExportableActivityInterface
{
    public const STATUSES = ['draft', 'published', 'archived'];

    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->type = 'note');
    }

    public function isPinned(): bool
    {
        return (bool) ($this->metadata['pinned'] ?? false);
    }

    public function getFormat(): string
    {
        return $this->metadata['format'] ?? 'markdown';
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

    public static function getSpecsSchema(): array
    {
        return [
            'format' => 'string|nullable',
            'pinned' => 'boolean|nullable',
        ];
    }
}
