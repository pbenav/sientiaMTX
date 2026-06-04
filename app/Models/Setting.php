<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted()
    {
        // Lifecycle events are now handled by App\Observers\SettingObserver
    }

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @param bool $ignoreEmpty If true, returns default if value is null or empty string
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
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value)
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
