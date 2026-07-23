<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Informe de servicio con verificación.
 *
 * Representa un informe generado sobre un servicio, con detalles
 * del trabajo realizado y estado de verificación.
 *
 * Campos clave:
 * - service_id: ID del servicio asociado
 * - user_id: ID del usuario que genera el informe
 * - type: Tipo de informe (ej: "completion", "issue", "feedback")
 * - details: Detalles del informe
 * - is_verified: Si el informe ha sido verificado
 *
 * @property-read int $service_id
 * @property-read int $user_id
 * @property-read string $type
 * @property-read string $details
 * @property-read bool $is_verified
 *
 * @property-read \App\Models\Service $service
 * @property-read \App\Models\User $user
 *
 * @mixin Builder
 */
class ServiceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'user_id',
        'type',
        'details',
        'is_verified'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Relación de pertenencia al servicio del informe.
     *
     * @return BelongsTo<\App\Models\Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relación de pertenencia al usuario que genera el informe.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
