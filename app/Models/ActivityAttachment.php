<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adjunto de actividad con control de acceso.
 *
 * Registra archivos asociados a una actividad, con soporte para
 * almacenamiento local y Google Drive, cálculo de tamaño en formato
 * legible, y verificación de permisos basada en la visibilidad de
 * la actividad y el equipo.
 *
 * Campos clave:
 * - uuid: Identificador único público
 * - activity_id: ID de la actividad a la que pertenece
 * - uploaded_by_id: ID del usuario que subió el archivo
 * - file_name: Nombre original del archivo
 * - file_path: Ruta al archivo en el disco
 * - disk: Disco de almacenamiento (local, google_drive, etc.)
 * - mime_type: Tipo MIME del archivo
 * - file_size: Tamaño en bytes
 * - label: Etiqueta descriptiva opcional
 *
 * @property-read string $uuid
 * @property-read int $activity_id
 * @property-read int $uploaded_by_id
 * @property-read string $file_name
 * @property-read string $file_path
 * @property-read string $disk
 * @property-read string $mime_type
 * @property-read int $file_size
 * @property-read string|null $label
 *
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\User $uploadedBy
 *
 * @mixin Builder
 */
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

    /**
     * Relación de pertenencia a la actividad adjunta.
     *
     * @return BelongsTo<\App\Models\Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Relación de pertenencia al usuario que subió el archivo.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * Verifica si un usuario puede acceder al adjunto.
     *
     * Comprueba que el adjunto pertenezca al equipo del usuario y que
     * el usuario tenga permiso para ver la actividad asociada.
     *
     * @param \App\Models\User $user Usuario a verificar
     * @param \App\Models\Team $team Equipo del usuario
     * @return bool True si el usuario puede acceder al adjunto
     */
    public function canBeAccessedBy(User $user, Team $team): bool
    {
        if (!$this->activity) {
            return false;
        }

        // Must belong to the team
        if ($this->activity->team_id !== $team->id) {
            return false;
        }

        // User must be able to view the activity
        return $user->can('view', $this->activity);
    }

    /**
     * Atributo accesible: tamaño del archivo en formato legible.
     *
     * @return string Tamaño en B, KB o MB
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    /**
     * Atributo accesible: URL para descargar el adjunto.
     *
     * Para archivos en Google Drive devuelve la URL directa.
     * Para archivos locales devuelve la ruta de descarga del equipo.
     *
     * @return string URL de descarga
     */
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

    /**
     * Atributo accesible: nombre del archivo.
     *
     * @return string Nombre del archivo
     */
    public function getFilenameAttribute(): string
    {
        return $this->attributes['file_name'] ?? '';
    }

    /**
     * Atributo accesible: tamaño del archivo en bytes.
     *
     * @return int Tamaño en bytes
     */
    public function getFilesizeAttribute(): int
    {
        return (int) ($this->attributes['file_size'] ?? 0);
    }

    /**
     * Verifica si el adjunto es una imagen.
     *
     * @return bool True si el MIME type comienza con "image/"
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Verifica si el adjunto es un PDF.
     *
     * @return bool True si el MIME type es "application/pdf"
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Verifica si el adjunto es compatible con OnlyOffice.
     *
     * Los archivos de Google Drive no se abren directamente en OnlyOffice.
     * Solo archivos locales con extensiones de oficina son compatibles.
     *
     * @return bool True si el archivo es compatible con OnlyOffice
     */
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
