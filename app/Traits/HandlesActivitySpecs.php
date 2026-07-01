<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait HandlesActivitySpecs
{
    /**
     * Exporta los metadatos específicos basándose en el esquema definido.
     */
    public function exportSpecs(): array
    {
        $meta = $this->metadata ?? [];
        $exported = [];
        $schemaKeys = array_keys(static::getSpecsSchema());

        foreach ($schemaKeys as $key) {
            $exported[$key] = $meta[$key] ?? null;
        }

        return $exported;
    }

    /**
     * Importa y valida los metadatos basándose estrictamente en el esquema definido.
     */
    public function importSpecs(array $specs): void
    {
        $schema = static::getSpecsSchema();
        
        // Validación estricta de seguridad para evitar inyección y desbordamiento de tipos
        $validator = Validator::make($specs, $schema);
        if ($validator->fails()) {
            throw new \InvalidArgumentException('Especificaciones inválidas: ' . implode(', ', $validator->errors()->all()));
        }

        $validated = $validator->validated();
        $meta = $this->metadata ?? [];

        foreach ($validated as $key => $value) {
            $meta[$key] = $value;
        }

        $this->metadata = $meta;
    }
}
