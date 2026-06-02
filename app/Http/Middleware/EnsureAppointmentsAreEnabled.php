<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppointmentsAreEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $team = $request->route('team');
            if ($team) {
                $teamId = $team instanceof \App\Models\Team ? $team->id : $team;
                $pivot = $request->user()->teams()->where('teams.id', $teamId)->first()?->pivot;
                
                if (!$pivot || !$pivot->allow_appointments) {
                    return redirect()->route('dashboard')->with('warning', 'No tienes habilitada la gestión de citas en este equipo.');
                }
            } else {
                if (!$request->user()->hasAppointmentsEnabled()) {
                    return redirect()->route('dashboard')->with('warning', 'La funcionalidad de Citas Previas no está habilitada para tu cuenta. Por favor, solicita a un administrador global que la active para tu perfil.');
                }
            }
        }

        return $next($request);
    }
}
