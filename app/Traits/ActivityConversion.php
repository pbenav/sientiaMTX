<?php

namespace App\Traits;

use App\Models\Activity;

/**
 * Trait ActivityConversion
 *
 * Proporciona métodos para rastrear la conversión entre actividades
 * (actividades que fueron convertidas de otra o que dieron origen a otra),
 * así como obtener todos los adjuntos relacionados.
 */
trait ActivityConversion
{
    /**
     * Determina si esta actividad fue deprecada por una conversión.
     * Una actividad se considera deprecada si está archivada Y tiene estado 'deprecated'
     * O si metadata['is_deprecated'] es true.
     */
    public function isDeprecatedByConversion(): bool
    {
        return $this->is_archived && ($this->status_value === 'deprecated' || data_get($this->metadata, 'is_deprecated', false));
    }

    /**
     * Obtiene la actividad a la que esta fue convertida (si existe).
     * Busca el UUID de destino en metadata['converted_to_uuid'].
     */
    public function getConvertedToActivityAttribute(): ?Activity
    {
        $toUuid = data_get($this->metadata, 'converted_to_uuid');
        return $toUuid ? static::where('uuid', $toUuid)->first() : null;
    }

    /**
     * Obtiene la actividad de la que esta fue convertida (si existe).
     * Busca el UUID de origen en metadata['converted_from_uuid'].
     */
    public function getConvertedFromActivityAttribute(): ?Activity
    {
        $fromUuid = data_get($this->metadata, 'converted_from_uuid');
        return $fromUuid ? static::where('uuid', $fromUuid)->first() : null;
    }

    /**
     * Obtiene todos los adjuntos de esta actividad.
     * Alias para obtener los attachments como un atributo calculado.
     */
    public function getAllAttachmentsAttribute()
    {
        return $this->attachments()
            ->get();
    }
}
