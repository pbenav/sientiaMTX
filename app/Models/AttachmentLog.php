<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registro de acceso a archivos adjuntos.
 *
 * Almacena un log de acciones realizadas sobre archivos adjuntos,
 * incluyendo el usuario que realizó la acción, el tipo de acción
 * y metadatos adicionales.
 *
 * Campos clave:
 * - attachment_id: ID del archivo adjunto asociado
 * - user_id: ID del usuario que realizó la acción
 * - action: Tipo de acción realizada (ej: "view", "download", "delete")
 * - metadata: Metadatos adicionales en formato array
 * - ip_address: Dirección IP del usuario
 *
 * @property-read int $attachment_id
 * @property-read int $user_id
 * @property-read string $action
 * @property-read array|null $metadata
 * @property-read string|null $ip_address
 *
 * @property-read \App\Models\TaskAttachment $attachment
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class AttachmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachment_id',
        'user_id',
        'action',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relación de pertenencia al archivo adjunto del registro.
     *
     * @return BelongsTo<\App\Models\TaskAttachment, $this>
     */
    public function attachment()
    {
        return $this->belongsTo(TaskAttachment::class, 'attachment_id');
    }

    /**
     * Relación de pertenencia al usuario que realizó la acción.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
