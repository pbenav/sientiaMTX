<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

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
        $setting = static::where('key', $key)->first();
        
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
