<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models\Activities;

use App\Models\Activity;
use App\Contracts\ExportableActivityInterface;

/**
 * Subtipo: Enlace
 *
 * metadata esperado:
 * {
 *   "url": "https://example.com",
 *   "og_title": "Título Open Graph",
 *   "og_description": "Descripción",
 *   "og_image": "https://example.com/image.jpg",
 *   "og_fetched_at": "2026-06-26T12:00:00Z"
 * }
 */
class LinkActivity extends Activity implements ExportableActivityInterface
{
    public const STATUSES = ['active', 'broken', 'archived'];

    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->type = 'link');
    }

    public function getUrl(): ?string
    {
        return $this->metadata['url'] ?? null;
    }

    public function getOgTitle(): ?string
    {
        return $this->metadata['og_title'] ?? $this->title;
    }

    public function getOgImage(): ?string
    {
        return $this->metadata['og_image'] ?? null;
    }

    public function isBroken(): bool
    {
        return $this->status_value === 'broken';
    }

    // ─── Implementación de ExportableActivityInterface ────────────────────────
    use \App\Traits\HandlesActivitySpecs;

    public static function getSpecsSchema(): array
    {
        return [
            'url'            => 'string|url|nullable',
            'og_title'       => 'string|nullable',
            'og_description' => 'string|nullable',
            'og_image'       => 'string|nullable',
            'og_fetched_at'  => 'string|nullable',
        ];
    }
}
