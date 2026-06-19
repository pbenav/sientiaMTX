<?php

namespace App\Models;

use App\Traits\HasDemoMasking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentVisitor extends Model
{
    use HasDemoMasking;

    protected array $demoSensitiveAttributes = [
        'first_name'   => 'name',
        'last_name'    => 'name',
        'dni'          => 'token',
        'email'        => 'email',
        'phone'        => 'phone',
        'city'         => 'text',
        'postal_code'  => 'phone',
        'observations' => 'text',
        'ip_address'   => 'token',
    ];
    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'email',
        'phone',
        'city',
        'postal_code',
        'observations',
        'consent_email',
        'consent_data',
        'consent_legal',
        'ip_address',
    ];

    protected $casts = [
        'consent_email' => 'boolean',
        'consent_data'  => 'boolean',
        'consent_legal' => 'boolean',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'visitor_id');
    }

    public function getFullNameAttribute(): string
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");
        
        // Transliterar on-the-fly si tiene caracteres árabes y no ha sido transliterado previamente
        if (preg_match('/\p{Arabic}/u', $fullName) && !preg_match('/\([a-zA-Z\s]+\)/', $fullName)) {
            if (class_exists(\Transliterator::class)) {
                $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
                $latin = $transliterator ? $transliterator->transliterate($fullName) : \Illuminate\Support\Str::transliterate($fullName);
            } else {
                $latin = \Illuminate\Support\Str::transliterate($fullName);
            }
            $latin = preg_replace('/[^a-zA-Z\s]/', '', $latin);
            $latin = ucwords(strtolower(trim($latin)));
            
            if ($latin) {
                return $fullName . ' (' . $latin . ')';
            }
        }
        
        return $fullName;
    }
}
