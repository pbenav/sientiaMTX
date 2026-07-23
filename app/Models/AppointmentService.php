<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Servicio de citas con modalidades y traducciones.
 *
 * Define los servicios disponibles para agendamiento de citas,
 * con soporte para modalidades (presencial, Google Meet, Jitsi),
 * precios, campos personalizados, protección de datos y
 * traducciones multiidioma.
 *
 * Campos clave:
 * - user_id: ID del usuario que ofrece el servicio
 * - team_id: ID del equipo al que pertenece
 * - name: Nombre del servicio
 * - modality: Modalidad(es) del servicio (array)
 * - description: Descripción del servicio
 * - duration_minutes: Duración estándar en minutos
 * - slot_duration_minutes: Duración de cada slot (null = usar default)
 * - max_per_slot: Máximo de citas por slot (null = usar default)
 * - price: Precio del servicio
 * - price_visible: Si el precio es visible públicamente
 * - sync_to_google_calendar: Si sincroniza con Google Calendar
 * - sync_to_google_tasks: Si sincroniza con Google Tasks
 * - is_active: Si el servicio está activo
 * - sort_order: Orden de aparición
 * - translations: Traducciones multiidioma
 * - custom_fields: Campos personalizados del formulario de reserva
 * - data_protection: Configuración de protección de datos
 *
 * @property-read int $user_id
 * @property-read int|null $team_id
 * @property-read string $name
 * @property-read array $modality
 * @property-read string|null $description
 * @property-read int $duration_minutes
 * @property-read int|null $slot_duration_minutes
 * @property-read int|null $max_per_slot
 * @property-read string $price
 * @property-read bool $price_visible
 * @property-read bool $sync_to_google_calendar
 * @property-read bool $sync_to_google_tasks
 * @property-read bool $is_active
 * @property-read int $sort_order
 * @property-read array $translations
 * @property-read array $custom_fields
 * @property-read array $data_protection
 * @property-read int $effective_slot_duration
 * @property-read int $effective_max_per_slot
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Team|null $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AppointmentSchedule> $schedules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Appointment> $appointments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AppointmentBlock> $blocks
 *
 * @mixin Builder
 */
class AppointmentService extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'modality',
        'description',
        'duration_minutes',
        'slot_duration_minutes',
        'max_per_slot',
        'price',
        'price_visible',
        'sync_to_google_calendar',
        'sync_to_google_tasks',
        'is_active',
        'sort_order',
        'translations',
        'custom_fields',
        'data_protection',
    ];

    const MODALITIES = [
        'presencial' => 'Presencial',
        'meet'       => 'Videoconferencia (Google Meet)',
        'jitsi'      => 'Videoconferencia (Jitsi)',
    ];

    protected $casts = [
        'modality'      => 'array',
        'price'                   => 'decimal:2',
        'price_visible'           => 'boolean',
        'sync_to_google_calendar' => 'boolean',
        'sync_to_google_tasks'    => 'boolean',
        'is_active'               => 'boolean',
        'translations'            => 'array',
        'custom_fields'           => 'array',
        'data_protection'         => 'array',
    ];

    /**
     * Accesor para obtener el nombre traducido al idioma actual, o el original si no existe.
     *
     * @param string $value Valor original del atributo
     * @return string Nombre traducido o original
     */
    public function getNameAttribute($value)
    {
        $locale = app()->getLocale();
        if ($locale !== 'es' && is_array($this->translations) && isset($this->translations[$locale]['name'])) {
            return $this->translations[$locale]['name'];
        }
        return $value;
    }

    /**
     * Accesor para obtener la descripción traducida, o la original si no existe.
     *
     * @param string $value Valor original del atributo
     * @return string Descripción traducida o original
     */
    public function getDescriptionAttribute($value)
    {
        $locale = app()->getLocale();
        if ($locale !== 'es' && is_array($this->translations) && isset($this->translations[$locale]['description'])) {
            return $this->translations[$locale]['description'];
        }
        return $value;
    }

    /**
     * Accesor para obtener los campos personalizados con sus nombres traducidos, si existen.
     *
     * @param string|array $value Valor original del atributo
     * @return array Campos personalizados con nombres traducidos
     */
    public function getCustomFieldsAttribute($value)
    {
        $fields = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($fields) || empty($fields)) {
            return $fields;
        }

        $locale = app()->getLocale();
        if ($locale !== 'es' && is_array($this->translations) && isset($this->translations[$locale]['custom_fields'])) {
            $translatedFields = $this->translations[$locale]['custom_fields'];

            foreach ($fields as &$field) {
                if (isset($field['id']) && isset($translatedFields[$field['id']])) {
                    $field['name'] = $translatedFields[$field['id']];
                }
            }
        }

        return $fields;
    }

    /**
     * Relación de pertenencia al usuario que ofrece el servicio.
     *
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación de pertenencia al equipo al que pertenece el servicio.
     *
     * @return BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relación uno-a-muchos con los horarios del servicio.
     *
     * @return HasMany<\App\Models\AppointmentSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(AppointmentSchedule::class, 'service_id');
    }

    /**
     * Relación uno-a-muchos con las citas del servicio.
     *
     * @return HasMany<\App\Models\Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }

    /**
     * Relación uno-a-muchos con los bloques del servicio.
     *
     * @return HasMany<\App\Models\AppointmentBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(AppointmentBlock::class, 'service_id');
    }

    /**
     * Obtiene la duración efectiva del slot.
     *
     * Si el servicio tiene slot_duration_minutes definido, lo devuelve.
     * De lo contrario, hereda el valor de la configuración del equipo.
     *
     * @return int Duración del slot en minutos
     */
    public function getEffectiveSlotDuration(): int
    {
        if ($this->slot_duration_minutes) {
            return $this->slot_duration_minutes;
        }
        $settings = $this->team ? $this->user->appointmentSettingsForTeam($this->team) : null;
        return $settings?->default_slot_duration ?? 15;
    }

    /**
     * Máximo efectivo por tramo: propio o heredado del setting del usuario.
     *
     * @return int Cantidad máxima de citas por slot
     */
    public function getEffectiveMaxPerSlot(): int
    {
        if ($this->max_per_slot) {
            return $this->max_per_slot;
        }
        $settings = $this->team ? $this->user->appointmentSettingsForTeam($this->team) : null;
        return $settings?->default_max_per_slot ?? 1;
    }

    /**
     * Scope: servicios activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
