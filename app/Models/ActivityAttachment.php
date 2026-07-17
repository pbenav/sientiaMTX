<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityAttachment extends Model
{
    use SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid', 'activity_id', 'uploaded_by_id',
        'file_name', 'file_path', 'disk', 'mime_type', 'file_size', 'label',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function getUrlAttribute(): string
    {
        if ($this->disk === 'google_drive') {
            return $this->file_path;
        }

        if (!$this->activity) {
            return '';
        }

        return route('teams.activities.attachments.download', [
            'team' => $this->activity->team_id,
            'activity' => $this->activity_id,
            'attachment' => $this->id
        ]);
    }

    public function getFilenameAttribute(): string
    {
        return $this->attributes['file_name'] ?? '';
    }

    public function getFilesizeAttribute(): int
    {
        return (int) ($this->attributes['file_size'] ?? 0);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getIsOfficeCompatibleAttribute(): bool
    {
        if ($this->disk === 'google_drive') {
            return false; // Archivos en GDrive no se abren en OnlyOffice directamente
        }

        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return in_array($extension, [
            // Word
            'doc', 'docx', 'rtf', 'odt', 'txt',
            // Excel
            'xls', 'xlsx', 'ods', 'csv',
            // Powerpoint
            'ppt', 'pptx', 'odp'
        ]);
    }
}
