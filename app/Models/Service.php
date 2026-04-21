<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'url',
        'icon',
        'status',
        'description',
        'status_updated_at',
        'sort_order'
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ServiceReport::class);
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'up' => 'emerald',
            'down' => 'red',
            'unstable' => 'amber',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'up' => __('Activo'),
            'down' => __('Caído'),
            'unstable' => __('Inestable'),
            default => __('Desconocido'),
        };
    }

    public function getIncidentHistory(): array
    {
        $tenDaysAgo = now()->subDays(10)->startOfDay();
        $reports = $this->reports()
            ->where('type', 'down')
            ->where('created_at', '>=', $tenDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        $history = [];
        for ($i = 9; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $history[] = $reports->get($date, 0);
        }
        return $history;
    }
}
