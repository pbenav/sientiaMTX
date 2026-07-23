<?php

namespace App\Models;

use App\Traits\HasDemoMasking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Datos del visitante de cita con enmascaramiento para demos.
 *
 * Registra la información de contacto y consentimiento de los
 * visitantes que reservan citas, con enmascaramiento automático
 * de datos sensibles en modo demo y transliteración de nombres
 * con caracteres árabes.
 *
 * Campos clave:
 * - first_name: Nombre del visitante
 * - last_name: Apellido del visitante
 * - dni: Documento nacional de identidad
 * - email: Correo electrónico
 * - phone: Teléfono de contacto
 * - city: Ciudad
 * - postal_code: Código postal
 * - observations: Observaciones del visitante
 * - consent_email: Si consentir contacto por email
 * - consent_data: Si consentir tratamiento de datos
 * - consent_legal: Si consentir términos legales
 * - ip_address: Dirección IP del visitante
 *
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string|null $dni
 * @property-read string|null $email
 * @property-read string|null $phone
 * @property-read string|null $city
 * @property-read string|null $postal_code
 * @property-read string|null $observations
 * @property-read bool $consent_email
 * @property-read bool $consent_data
 * @property-read bool $consent_legal
 * @property-read string|null $ip_address
 * @property-read string $full_name
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Appointment> $appointments
 *
 * @mixin Builder
 */
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

    /**
     * Relación uno-a-muchos con las citas del visitante.
     *
     * @return HasMany<\App\Models\Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'visitor_id');
    }

    /**
     * Atributo accesible: nombre completo del visitante.
     *
     * Si el nombre contiene caracteres árabes, los translitera
     * y añade la versión latina entre paréntesis.
     *
     * @return string Nombre completo con transliteración opcional
     */
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
