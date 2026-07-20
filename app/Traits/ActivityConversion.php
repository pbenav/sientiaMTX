<?php

namespace App\Traits;

use App\Models\Activity;

trait ActivityConversion
{
    public function isDeprecatedByConversion(): bool
    {
        return $this->is_archived && ($this->status_value === 'deprecated' || data_get($this->metadata, 'is_deprecated', false));
    }

    public function getConvertedToActivityAttribute(): ?Activity
    {
        $toUuid = data_get($this->metadata, 'converted_to_uuid');
        return $toUuid ? static::where('uuid', $toUuid)->first() : null;
    }

    public function getConvertedFromActivityAttribute(): ?Activity
    {
        $fromUuid = data_get($this->metadata, 'converted_from_uuid');
        return $fromUuid ? static::where('uuid', $fromUuid)->first() : null;
    }

    public function getAllAttachmentsAttribute()
    {
        return $this->attachments()
            ->get();
    }
}
