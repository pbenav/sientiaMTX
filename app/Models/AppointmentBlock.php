<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentBlock extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'start_datetime',
        'end_datetime',
        'reason',
        'notify_affected',
    ];

    protected $casts = [
        'start_datetime'   => 'datetime',
        'end_datetime'     => 'datetime',
        'notify_affected'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    /**
     * Scope: bloques activos (cuyo end_datetime >= ahora)
     */
    public function scopeActive($query)
    {
        return $query->where('end_datetime', '>=', now());
    }
}
