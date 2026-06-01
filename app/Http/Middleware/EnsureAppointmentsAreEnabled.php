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
        if ($request->user() && !$request->user()->hasAppointmentsEnabled()) {
            return redirect()->route('dashboard')->with('warning', 'La funcionalidad de Citas Previas no está habilitada para tu cuenta. Por favor, solicita a tu coordinador de equipo que la active para tu perfil.');
        }

        return $next($request);
    }
}
