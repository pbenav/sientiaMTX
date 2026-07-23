<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceReport;
use App\Models\Team;
use App\Traits\AwardsGamification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para la gestión de servicios y monitoreo de estado (reportes de incidencias).
 *
 * Permite a coordinadores crear, actualizar y eliminar servicios de un equipo.
 * Los miembros pueden reportar estados (up/down) con un sistema de cooldown y gamificación.
 */
class ServiceController extends Controller
{
    use AwardsGamification;
    /**
     * Crea un nuevo servicio para el equipo (solo coordinadores).
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener name (obligatorio), url, icon, description (opcionales)
     * @param  \App\Models\Team  $team  Equipo al que pertenece el servicio
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito
     */
    public function store(Request $request, Team $team)
    {
        if (!$team->isCoordinator(auth()->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url',
            'icon' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $team->services()->create($validated);

        return back()->with('success', __('Servicio añadido correctamente.'));
    }

    /**
     * Registra un reporte de estado (up/down) para un servicio.
     *
     * Implementa un cooldown de 5 minutos por usuario/servicio/tipo para evitar spam.
     * Si se reciben reportes de caída (down) → estado inmediato. Si hay reportes de
     * recuperación (up) → estado inmediato. Otorga puntos de gamificación 'Bono Centinela'.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener type (up|down), details (opcional)
     * @param  \App\Models\Team  $team  Equipo del servicio
     * @param  \App\Models\Service  $service  Servicio a reportar
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse Respuesta JSON o redirección con resultado
     */
    public function report(Request $request, Team $team, Service $service)
    {
        $validated = $request->validate([
            'type' => 'required|in:up,down',
            'details' => 'nullable|string',
        ]);

        // Check for cooldown (1 report per hour per service per user)
        $exists = ServiceReport::where('service_id', $service->id)
            ->where('user_id', auth()->id())
            ->where('type', $validated['type'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($exists) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Ya has reportado este estado recientemente.')
                ], 422);
            }
            return back()->with('error', __('Ya has reportado este estado recientemente.'));
        }

        DB::transaction(function () use ($service, $validated, $team) {
            $report = ServiceReport::create([
                'service_id' => $service->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'details' => $validated['details'] ?? null,
            ]);

            // Simple status update logic
            $recentDown = ServiceReport::where('service_id', $service->id)
                ->where('type', 'down')
                ->where('created_at', '>=', now()->subHours(2))
                ->count();

            $recentUp = ServiceReport::where('service_id', $service->id)
                ->where('type', 'up')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->count();

            $oldStatus = $service->status;

            if ($validated['type'] === 'down') {
                // Immediate fall: human report dictates the state immediately
                if ($recentDown >= 1) {
                    $service->update(['status' => 'down', 'status_updated_at' => now()]);
                    
                    // Gamification logic...
                    if ($recentDown === 1) {
                        $this->awardServiceReportingPoints(
                            auth()->user(), 
                            $team->id, 
                            __('Bono Centinela: Reporte de caída verificado en :service', ['service' => $service->name])
                        );
                    }
                }
            } else {
                // Immediate recovery: if anyone says 'up', we promote back to active immediately
                if ($recentUp >= 1) {
                    $service->update(['status' => 'up', 'status_updated_at' => now()]);
                }
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Estado reportado.¡Gracias por colaborar!'),
                'new_status' => $service->fresh()->status,
                'new_status_label' => $service->fresh()->getStatusLabel(),
                'new_status_color' => $service->fresh()->getStatusColor()
            ]);
        }

        return back()->with('success', __('Estado reportado.¡Gracias por colaborar!'));
    }

    /**
     * Actualiza el orden de visualización de los servicios del equipo (solo coordinadores).
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener ids (array de IDs válidos)
     * @param  \App\Models\Team  $team  Equipo cuyos servicios se reordenan
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true
     */
    public function reorder(Request $request, Team $team)
    {
        if (!$team->isCoordinator(auth()->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:services,id'
        ]);

        foreach ($validated['ids'] as $index => $id) {
            Service::where('id', $id)->where('team_id', $team->id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Actualiza los datos de un servicio del equipo (solo coordinadores).
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener name (obligatorio), url, icon, description (opcionales)
     * @param  \App\Models\Team  $team  Equipo del servicio
     * @param  \App\Models\Service  $service  Servicio a actualizar
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito
     */
    public function update(Request $request, Team $team, Service $service)
    {
        if (!$team->isCoordinator(auth()->user()) || $service->team_id !== $team->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url',
            'icon' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $service->update($validated);

        return back()->with('success', __('Servicio actualizado correctamente.'));
    }

    /**
     * Elimina un servicio del equipo (solo coordinadores).
     *
     * @param  \App\Models\Team  $team  Equipo del servicio
     * @param  \App\Models\Service  $service  Servicio a eliminar
     * @return \Illuminate\Http\RedirectResponse Redirección con mensaje de éxito
     */
    public function destroy(Team $team, Service $service)
    {
        if (!$team->isCoordinator(auth()->user())) {
            abort(403);
        }

        $service->delete();

        return back()->with('success', __('Servicio eliminado.'));
    }

    /**
     * Obtiene los últimos incidentes reportados para un servicio (API JSON).
     *
     * Formatea los datos para consumo frontend con etiquetas de tipo (activo/incidencia),
     * colores, nombre del reportero (humano o sistema), timestamps y formato humano.
     *
     * @param  \App\Models\Team  $team  Equipo del servicio
     * @param  \App\Models\Service  $service  Servicio a consultar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true y array de incidentes formateados
     */
    public function incidents(Team $team, Service $service)
    {
        if ($service->team_id !== $team->id) {
            abort(403);
        }

        $incidents = $service->getLatestIncidents(20);

        return response()->json([
            'success' => true,
            'incidents' => $incidents->map(function($i) {
                return [
                    'type' => $i->type,
                    'type_label' => $i->type === 'up' ? __('Activo') : __('Incidencia'),
                    'type_color' => $i->type === 'up' ? 'emerald' : 'red',
                    'reporter' => $i->user ? $i->user->name : __('Sientia Sentinel (Automatizado)'),
                    'reporter_type' => $i->user ? 'human' : 'system',
                    'date' => $i->created_at->translatedFormat('d M, H:i'),
                    'diff' => $i->created_at->diffForHumans(),
                    'details' => $i->details
                ];
            })
        ]);
    }
}
