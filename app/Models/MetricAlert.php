<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricAlert extends Model
{
    use HasFactory;

    protected $table = 'metric_alerts';

    protected $fillable = [
        'user_id', 'team_id', 'category', 'severity', 'code',
        'title', 'message', 'data', 'is_read', 'read_at',
        'resolved_at', 'action_url',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'resolved_at' => now(),
            'is_read' => true,
            'read_at' => $this->read_at ?? now(),
        ]);
    }
}
