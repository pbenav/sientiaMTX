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
        return trim("{$this->first_name} {$this->last_name}");
    }
}
