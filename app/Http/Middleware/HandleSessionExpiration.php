<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

class HandleSessionExpiration
{
    /**
     * Handle an incoming request.
     * Intercepts TokenMismatchException (CSRF token expired / session expired)
     * and redirects to the login page instead of showing a 419 error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (TokenMismatchException $e) {
            // AJAX / JSON requests: return JSON 419 so JS can handle it
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La sesión ha expirado. Por favor, recarga la página e inicia sesión de nuevo.',
                    'expired' => true,
                ], 419);
            }

            // Normal requests: redirect to home directly to avoid showing 419 error page
            // The 419 view (resources/views/errors/419.blade.php) handles the redirect if it reaches the error handler
            \Illuminate\Support\Facades\Log::warning("Session expiration intercepted for URL: " . $request->fullUrl());
            
            return redirect()->route('dashboard')
                ->with('warning', 'Tu sesión ha expirado. Por favor, vuelve a iniciar sesión.');
        }
    }
}
