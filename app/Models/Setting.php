<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configuración de la aplicación con helpers get/set.
 *
 * Almacena configuraciones clave-valor de la aplicación, con
 * métodos estáticos para obtener y establecer valores de forma
 * segura, incluyendo soporte para valores por defecto y
 * validación de valores vacíos.
 *
 * Campos clave:
 * - key: Clave única de la configuración
 * - value: Valor de la configuración
 *
 * @property-read string $key
 * @property-read string|null $value
 *
 * @mixin Builder
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Obtiene el valor de una configuración por clave.
     *
     * @param string $key Clave de la configuración
     * @param mixed $default Valor por defecto si no se encuentra
     * @param bool $ignoreEmpty Si true, devuelve el default si el valor es null o vacío
     * @return mixed
     */
    public static function get(string $key, $default = null, bool $ignoreEmpty = false)
    {
        try {
            $setting = static::where('key', $key)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return $default;
        }

        if (!$setting) {
            return $default;
        }

        if ($ignoreEmpty && empty($setting->value)) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Establece el valor de una configuración por clave.
     *
     * @param string $key Clave de la configuración
     * @param mixed $value Valor a establecer
     * @return void
     */
    public static function set(string $key, $value)
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
