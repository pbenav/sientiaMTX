<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Micrositio con contenido HTML/CSS y datos de ubicación.
 *
 * Representa un micrositio vinculado a un equipo y usuario, con
 * contenido personalizable, estado de publicación y datos geográficos.
 *
 * Campos clave:
 * - team_id: ID del equipo al que pertenece
 * - user_id: ID del usuario creador
 * - title: Título del micrositio
 * - slug: Slug único del micrositio
 * - html_content: Contenido HTML del micrositio
 * - css_content: Estilos CSS del micrositio
 * - is_published: Estado de publicación
 * - latitude: Latitud de la ubicación
 * - longitude: Longitud de la ubicación
 * - address: Dirección del micrositio
 * - city: Ciudad
 * - province: Provincia
 * - zip_code: Código postal
 * - views: Número de vistas
 *
 * @property-read int $team_id
 * @property-read int|null $user_id
 * @property-read string $title
 * @property-read string $slug
 * @property-read string|null $html_content
 * @property-read string|null $css_content
 * @property-read bool $is_published
 * @property-read float|null $latitude
 * @property-read float|null $longitude
 * @property-read string|null $address
 * @property-read string|null $city
 * @property-read string|null $province
 * @property-read string|null $zip_code
 * @property-read int $views
 *
 * @property-read \App\Models\Team $team
 * @property-read \App\Models\User|null $user
 *
 * @mixin Builder
 */
class Microsite extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::deleted(function ($microsite) {
            $microsite->slug = $microsite->slug . '-deleted-' . time();
            $microsite->saveQuietly();
        });
    }

    protected $fillable = [
        'team_id',
        'user_id',
        'title',
        'slug',
        'html_content',
        'css_content',
        'is_published',
        'latitude',
        'longitude',
        'address',
        'city',
        'province',
        'zip_code',
        'views',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'views' => 'integer',
    ];

    /**
     * Relación de pertenencia al equipo del micrositio.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación de pertenencia al usuario creador del micrositio.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
