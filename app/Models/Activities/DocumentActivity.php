<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Documento
 *
 * metadata esperado:
 * {
 *   "file_path": "storage/docs/contrato.pdf",
 *   "file_name": "contrato.pdf",
 *   "mime_type": "application/pdf",
 *   "file_size": 245760,
 *   "disk": "local",
 *   "version": "1.0",
 *   "tags": ["legal", "firmado"],
 *   "chapters": [...]
 * }
 */
class DocumentActivity extends Activity implements ExportableActivityInterface
{
    public const STATUSES = ['draft', 'review', 'approved', 'rejected', 'archived'];

    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->type = 'document');
    }

    public function getFilePath(): ?string
    {
        return $this->metadata['file_path'] ?? null;
    }

    public function getMimeType(): ?string
    {
        return $this->metadata['mime_type'] ?? null;
    }

    public function isApproved(): bool
    {
        return $this->status_value === 'approved';
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

    public static function getSpecsSchema(): array
    {
        return [
            'file_path' => 'string|nullable',
            'file_name' => 'string|nullable',
            'mime_type' => 'string|nullable',
            'file_size' => 'integer|nullable',
            'disk'      => 'string|nullable',
            'version'   => 'string|nullable',
            'tags'      => 'array|nullable',
            'chapters'  => 'array|nullable',
        ];
    }
}
