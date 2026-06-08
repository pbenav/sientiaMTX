<?php

namespace App\Http\Controllers\Microsite;

use App\Http\Controllers\Controller;
use App\Models\Microsite;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Http\Requests\Microsite\StoreMicrositeRequest;
use App\Http\Requests\Microsite\UpdateMicrositeRequest;

class MicrositeController extends Controller
{
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

    public function create(Team $team)
    {
        $user = auth()->user();
        if (!($team->settings['microsites_enabled'] ?? false) || !$user->teams()->where('team_id', $team->id)->first()->pivot->allow_microsites) {
            abort(403, 'No tienes permiso para gestionar micrositios.');
        }

        return view('microsites.create', compact('team'));
    }

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

    public function edit(Team $team, Microsite $microsite)
    {
        if ($microsite->user_id !== auth()->id() || $microsite->team_id !== $team->id) {
            abort(403);
        }

        return view('microsites.edit', compact('team', 'microsite'));
    }

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

    public function destroy(Team $team, Microsite $microsite)
    {
        if ($microsite->user_id !== auth()->id() || $microsite->team_id !== $team->id) {
            abort(403);
        }

        $microsite->delete();

        return redirect()->route('teams.microsites.index', $team)->with('success', 'Micrositio eliminado correctamente.');
    }
}
