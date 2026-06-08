<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Microsite extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::deleted(function ($microsite) {
            $microsite->slug = $microsite->slug . '-deleted-' . time();
            $microsite->saveQuietly();
        });
    }

    protected $fillable = [
        'team_id',
        'user_id',
        'title',
        'slug',
        'html_content',
        'css_content',
        'is_published',
        'latitude',
        'longitude',
        'address',
        'city',
        'province',
        'zip_code',
        'views',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'views' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
