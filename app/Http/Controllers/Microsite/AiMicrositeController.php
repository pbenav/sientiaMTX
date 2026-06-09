<?php

namespace App\Http\Controllers\Microsite;

use App\Http\Controllers\Controller;
use App\Models\Microsite;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiMicrositeController extends Controller
{
    /**
     * Crea un micrositio rápidamente desde el asistente Ax.ia.
     * Usa los datos del usuario (geo, equipo) como valores por defecto.
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'html'     => 'nullable|string',
            'css'      => 'nullable|string',
            'title'    => 'nullable|string|max:255',
            'team_id'  => 'nullable|integer|exists:teams,id',
        ]);

        $user = auth()->user();

        // Buscar el equipo: el solicitado, o el primero con micrositios habilitados
        $team = null;

        if ($request->team_id) {
            $team = Team::find($request->team_id);
        }

        if (!$team) {
            // Obtener el primer equipo del usuario que tenga micrositios habilitados
            $team = $user->teams()
                ->wherePivot('allow_microsites', true)
                ->whereJsonContains('settings->microsites_enabled', true)
                ->first();
        }

        if (!$team) {
            // Último recurso: cualquier equipo del usuario
            $team = $user->teams()->first();
        }

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes ningún equipo disponible para crear el micrositio.',
            ], 422);
        }

        // Comprobar que el usuario tiene permiso de micrositios en ese equipo
        $pivot = $user->teams()->where('team_id', $team->id)->first()?->pivot;
        $micrositesEnabled = $team->settings['microsites_enabled'] ?? false;
        $userAllowed       = $pivot?->allow_microsites ?? false;

        if (!$micrositesEnabled || !$userAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para crear micrositios en este equipo.',
            ], 403);
        }

        // Generar título y slug únicos
        $baseTitle = $request->title ?: ('Micrositio Ax.ia - ' . now()->format('d/m/Y H:i'));
        $baseSlug  = Str::slug($baseTitle);
        $slug      = $baseSlug;
        $suffix    = 1;

        while (Microsite::where('slug', $slug)->whereNull('deleted_at')->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        // Obtener datos de geolocalización del micrositio más reciente del usuario como plantilla
        $lastMicrosite = Microsite::where('user_id', $user->id)
            ->whereNotNull('latitude')
            ->latest()
            ->first();

        $microsite = Microsite::create([
            'team_id'      => $team->id,
            'user_id'      => $user->id,
            'title'        => $baseTitle,
            'slug'         => $slug,
            'html_content' => $request->html ?? '',
            'css_content'  => $request->css ?? '',
            'is_published' => false,
            'latitude'     => $lastMicrosite?->latitude ?? null,
            'longitude'    => $lastMicrosite?->longitude ?? null,
            'address'      => $lastMicrosite?->address ?? null,
            'city'         => $lastMicrosite?->city ?? null,
            'province'     => $lastMicrosite?->province ?? null,
            'zip_code'     => $lastMicrosite?->zip_code ?? null,
        ]);

        $editUrl = route('teams.microsites.edit', [$team, $microsite]);

        return response()->json([
            'success'  => true,
            'message'  => '¡Micrositio creado con éxito!',
            'edit_url' => $editUrl,
            'microsite' => [
                'id'    => $microsite->id,
                'title' => $microsite->title,
                'slug'  => $microsite->slug,
            ],
            'team' => [
                'id'   => $team->id,
                'name' => $team->name,
            ],
        ]);
    }
}
