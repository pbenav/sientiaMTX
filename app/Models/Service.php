<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


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

    public function hasUserReportedRecently($userId, $type = null): bool
    {
        $query = $this->reports()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(5));

        if ($type) {
            $query->where('type', $type);
        }

        return $query->exists();
    }

    public function getRecentUpReportsCount(): int
    {
        return $this->reports()
            ->where('type', 'up')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
    }

    public function getRecentDownReportsCount(): int
    {
        return $this->reports()
            ->where('type', 'down')
            ->where('created_at', '>=', now()->subHours(2))
            ->count();
    }

    public function getIncidentHistory(): array
    {
        // Fetch reports from the last 3 hours (180 minutes)
        $threeHoursAgo = now()->subHours(3);
        
        // Get IDs of all report categories during this window to identify down periods
        $reports = $this->reports()
            ->where('type', 'down')
            ->where('created_at', '>=', $threeHoursAgo)
            ->get();

        $history = [];
        $slices = 18; // 18 slices of 10 minutes = 3 hours
        
        for ($i = $slices - 1; $i >= 0; $i--) {
            $sliceStart = now()->subMinutes(($i + 1) * 10);
            $sliceEnd = now()->subMinutes($i * 10);
            
            // Check if there was ANY 'down' event recorded during this 10 minute slice
            $count = $reports->filter(function($report) use ($sliceStart, $sliceEnd) {
                return $report->created_at >= $sliceStart && $report->created_at < $sliceEnd;
            })->count();

            $history[] = $count;
        }
        
        return $history;
    }
}
