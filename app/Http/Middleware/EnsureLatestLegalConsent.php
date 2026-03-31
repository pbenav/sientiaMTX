<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLatestLegalConsent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (auth()->guest()) {
            return $next($request);
        }

        // Permitir rutas legales para evitar bucles de redirección
        if ($request->routeIs('privacy') || $request->routeIs('terms') || $request->routeIs('cookies') || 
            $request->routeIs('legal.reconsent') || $request->routeIs('legal.accept')) {
            return $next($request);
        }

        $user = auth()->user();
        $legalUpdatedAt = \App\Models\Setting::get('legal_updated_at');

        // Si el usuario NUNCA ha aceptado los términos, obligar a la primera aceptación
        if (!$user->privacy_policy_accepted_at) {
            return redirect()->route('legal.reconsent');
        }

        // Si hay una actualización global marcada, comparar fechas
        if ($legalUpdatedAt) {
            $legalUpdatedAt = \Carbon\Carbon::parse($legalUpdatedAt);
            if (\Carbon\Carbon::parse($user->privacy_policy_accepted_at)->lt($legalUpdatedAt)) {
                return redirect()->route('legal.reconsent');
            }
        }

        return $next($request);
    }
}
