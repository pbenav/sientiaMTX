<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricReport extends Model
{
    use HasFactory;

    protected $table = 'metric_reports';

    protected $fillable = [
        'user_id', 'team_id', 'type', 'period_label',
        'period_start', 'period_end', 'summary', 'charts_data',
        'insights', 'status', 'error_message', 'format',
        'file_path', 'generated_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'charts_data' => 'array',
        'insights' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }
}
