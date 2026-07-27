<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentSlotOverride extends Model
{
    protected $fillable = [
        'service_id',
        'date',
        'time',
        'extra_capacity',
    ];
}
