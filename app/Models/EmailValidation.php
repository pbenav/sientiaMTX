<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailValidation extends Model
{
    protected $fillable = [
        'visitor_id',
        'email',
        'domain',
        'is_valid',
        'verification_method',
        'verification_reason',
        'mx_count',
        'mx_host',
        'verified_at',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'mx_count' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function visitor()
    {
        return $this->belongsTo(AppointmentVisitor::class);
    }
}
