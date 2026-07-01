<?php

namespace App\Observers;

use App\Models\Setting;
use App\Models\SecurityLog;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    /**
     * Handle the Setting "saved" event.
     */
    public function saved(Setting $setting): void
    {
        if (auth()->check()) {
            SecurityLog::log(
                'setting.updated',
                "Configuración modificada: '{$setting->key}'"
            );
        }

        Cache::put('setting_' . $setting->key, null, 1);
    }
}
