<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricSnapshot extends Model
{
    use HasFactory;

    protected $table = 'metric_snapshots';

    protected $fillable = [
        'uuid', 'user_id', 'team_id', 'type', 'snapshot_date',
        'metrics', 'trends', 'alerts',
        'wellness_score', 'productivity_score', 'engagement_score',
        'mood_index', 'stress_index', 'energy_index', 'satisfaction_index',
        'burnout_risk_score', 'burnout_risk_level',
        'activities_completed', 'activities_in_progress', 'activities_pending', 'activities_overdue',
        'completion_rate', 'on_time_delivery', 'estimation_accuracy',
        'hours_logged', 'hours_overtime', 'streak_days',
        'team_wellness_avg', 'team_size', 'members_overloaded', 'members_underloaded',
        'nudge_responsiveness', 'work_life_balance_index',
    ];

    protected $casts = [
        'metrics' => 'array',
        'trends' => 'array',
        'alerts' => 'array',
        'snapshot_date' => 'date',
        'wellness_score' => 'decimal:2',
        'productivity_score' => 'decimal:2',
        'engagement_score' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'on_time_delivery' => 'decimal:2',
        'estimation_accuracy' => 'decimal:2',
        'hours_logged' => 'decimal:2',
        'hours_overtime' => 'decimal:2',
        'work_life_balance_index' => 'decimal:2',
        'team_wellness_avg' => 'decimal:2',
        'is_read' => 'boolean',
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

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('snapshot_date', $date);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
