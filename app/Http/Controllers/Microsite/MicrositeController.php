<?php

namespace App\Http\Controllers\Microsite;

use App\Http\Controllers\Controller;
use App\Models\Microsite;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Http\Requests\Microsite\StoreMicrositeRequest;
use App\Http\Requests\Microsite\UpdateMicrositeRequest;

/**
 * Controlador de administración de micrositios dentro de un equipo.
 *
 * Permite a los usuarios con permisos crear, editar, listar y eliminar micrositios
 * vinculados a un equipo. Incluye verificación de permisos a nivel de equipo y pivot
 * (allow_microsites). Los micrositios almacenan HTML, CSS y metadatos de publicación.
 *
 * Rutas asociadas:
 *   - GET /teams/{team}/microsites
 *   - GET /teams/{team}/microsites/create
 *   - POST /teams/{team}/microsites
 *   - GET /teams/{team}/microsites/{microsite}/edit
 *   - PUT/PATCH /teams/{team}/microsites/{microsite}
 *   - DELETE /teams/{team}/microsites/{microsite}
 */
class MicrositeController extends Controller
{
    /**
     * Muestra la lista de micrositios del usuario dentro de un equipo.
     *
     * Verifica que los micrositios estén habilitados para el equipo y que el usuario
     * tenga permiso de gestión de micrositios.
     *
     * @param Team $team
     * @return \Illuminate\View\View
     */
    public function index(Team $team)
    {
        $user = auth()->user();
        
        // Comprobar permiso a nivel de equipo y usuario
        if (!($team->settings['microsites_enabled'] ?? false) || !$user->teams()->where('team_id', $team->id)->first()->pivot->allow_microsites) {
            abort(403, 'No tienes permiso para gestionar micrositios.');
        }

        $microsites = Microsite::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('microsites.index', compact('team', 'microsites'));
    }

    /**
     * Muestra el formulario de creación de un nuevo micrositio.
     *
     * Verifica los permisos de micrositios del usuario y equipo.
     *
     * @param Team $team
     * @return \Illuminate\View\View
     */
    public function create(Team $team)
    {
        $user = auth()->user();
        if (!($team->settings['microsites_enabled'] ?? false) || !$user->teams()->where('team_id', $team->id)->first()->pivot->allow_microsites) {
            abort(403, 'No tienes permiso para gestionar micrositios.');
        }

        return view('microsites.create', compact('team'));
    }

    /**
     * Crea un nuevo micrositio para el usuario autenticado.
     *
     * Utiliza StoreMicrositeRequest para la validación. Asocia el micrositio al equipo
     * y al usuario actual, y determina el estado de publicación.
     *
     * @param StoreMicrositeRequest $request
     * @param Team $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreMicrositeRequest $request, Team $team)
    {
        $data = $request->validated();
        $data['team_id'] = $team->id;
        $data['user_id'] = auth()->id();
        $data['is_published'] = $request->has('is_published');
        
        // No purificamos el HTML ni el CSS porque rompería las clases de Tailwind y la estructura
        // Al ser usuarios autenticados y con permiso de equipo, confiamos en su input de código.

        Microsite::create($data);

        return redirect()->route('teams.microsites.index', $team)->with('success', 'Micrositio creado correctamente.');
    }

    /**
     * Muestra el formulario de edición de un micrositio existente.
     *
     * Verifica que el micrositio pertenezca al usuario autenticado y al equipo.
     *
     * @param Team $team
     * @param Microsite $microsite
     * @return \Illuminate\View\View
     */
    public function edit(Team $team, Microsite $microsite)
    {
        if ($microsite->user_id !== auth()->id() || $microsite->team_id !== $team->id) {
            abort(403);
        }

        return view('microsites.edit', compact('team', 'microsite'));
    }

    /**
     * Actualiza un micrositio existente.
     *
     * Utiliza UpdateMicrositeRequest para la validación. Verifica la propiedad del micrositio.
     *
     * @param UpdateMicrositeRequest $request
     * @param Team $team
     * @param Microsite $microsite
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateMicrositeRequest $request, Team $team, Microsite $microsite)
    {
        if ($microsite->user_id !== auth()->id() || $microsite->team_id !== $team->id) {
            abort(403);
        }

        $data = $request->validated();
        $data['is_published'] = $request->has('is_published');

        // No purificamos el HTML ni el CSS para mantener las clases de Tailwind y estructura.

        $microsite->update($data);

        return redirect()->route('teams.microsites.index', $team)->with('success', 'Micrositio actualizado correctamente.');
    }

    /**
     * Elimina un micrositio.
     *
     * Solo el propietario del micrositio puede eliminarlo.
     *
     * @param Team $team
     * @param Microsite $microsite
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team, Microsite $microsite)
    {
        if ($microsite->user_id !== auth()->id() || $microsite->team_id !== $team->id) {
            abort(403);
        }

        $microsite->delete();

        return redirect()->route('teams.microsites.index', $team)->with('success', 'Micrositio eliminado correctamente.');
    }
}
