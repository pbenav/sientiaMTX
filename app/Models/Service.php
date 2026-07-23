<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Servicio / Service: recurso de un equipo con monitoreo de estado.
 *
 * Estados: up (activo), down (caído), unstable (inestable)
 * Colores: emerald, red, amber
 *
 * Permite reportes de estado vía ServiceReport y calcula métricas de incidentes.
 */
class Service extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'url',
        'icon',
        'status',
        'description',
        'status_updated_at',
        'sort_order',
    ];

    /**
     * Casting de atributos.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'status_updated_at' => 'datetime',
    ];

    /**
     * Equipo propietario del servicio.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Reportes de estado de este servicio.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ServiceReport::class);
    }

    /**
     * Color de Tailwind asociado al estado actual.
     *
     * @return string emerald, red, amber, o gray
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'up' => 'emerald',
            'down' => 'red',
            'unstable' => 'amber',
            default => 'gray',
        };
    }

    /**
     * Etiqueta legible del estado actual.
     *
     * @return string Activo, Caído, Inestable, o Desconocido
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'up' => __('Activo'),
            'down' => __('Caído'),
            'unstable' => __('Inestable'),
            default => __('Desconocido'),
        };
    }

    /**
     * Verifica si un usuario reportó el estado recientemente.
     *
     * @param  int  $userId  ID del usuario
     * @param  string|null  $type  Tipo de reporte (up/down), opcional
     * @return bool true si existe reporte del mismo tipo en los últimos 5 minutos
     */
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

    /**
     * Cantidad de reportes "up" recientes en las últimas 15 minutos.
     *
     * Si hubo un reporte "down" en las últimas 15 minutos, el conteo
     * comienza desde ese momento (no desde 15 min atrás).
     */
    public function getRecentUpReportsCount(): int
    {
        $lastDown = $this->reports()->where('type', 'down')->latest()->first();
        $since = now()->subMinutes(15);

        if ($lastDown && $lastDown->created_at > $since) {
            $since = $lastDown->created_at;
        }

        return $this->reports()
            ->where('type', 'up')
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Cantidad de reportes "down" recientes en las últimas 2 horas.
     *
     * Si hubo un reporte "up" en las últimas 2 horas, el conteo
     * comienza desde ese momento (no desde 2 horas atrás).
     */
    public function getRecentDownReportsCount(): int
    {
        $lastUp = $this->reports()->where('type', 'up')->latest()->first();
        $since = now()->subHours(2);

        if ($lastUp && $lastUp->created_at > $since) {
            $since = $lastUp->created_at;
        }

        return $this->reports()
            ->where('type', 'down')
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Historial de incidentes de las últimas 3 horas.
     *
     * Retorna un array de 18 enteros (uno por cada slice de 10 minutos)
     * donde cada valor es la cantidad de reportes "down" en ese slice.
     *
     * @return list<int> Array de 18 enteros
     */
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

    /**
     * Últimos incidentes reportados para este servicio.
     *
     * @param  int  $limit  Cantidad máxima de incidentes a retornar (default: 10)
     * @return \Illuminate\Database\Eloquent\Collection<int, ServiceReport>
     */
    public function getLatestIncidents(int $limit = 10)
    {
        return $this->reports()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
