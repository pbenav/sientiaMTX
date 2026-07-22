<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

trait UserProfile
{
    /**
     * Get the URL to the user's profile photo.
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->profile_photo_path) {
                return Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path);
            }

            return $this->defaultProfilePhotoUrl();
        });
    }

    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     *
     * @return string
     */
    protected function defaultProfilePhotoUrl()
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the disk that profile photos should be stored on.
     *
     * @return string
     */
    public function profilePhotoDisk()
    {
        return isset($_ENV['VAPOR_ARTIFACT_NAME']) ? 's3' : 'public';
    }

    /**
     * Acceso seguro al token de Google para evitar errores de descifrado en la transición.
     */
    protected function googleToken(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                try {
                    return decrypt($value, true); // Intenta descifrar
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    return null; // Si falla (viejo/corrupto), ignorar
                }
            },
            set: fn ($value) => $value ? encrypt($value, true) : null,
        );
    }

    /**
     * Acceso seguro al refresh token de Google.
     */
    protected function googleRefreshToken(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                try {
                    return decrypt($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    return null;
                }
            },
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }
}
