<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requireApproval = \App\Models\Setting::get('require_approval', true);

        if ($requireApproval && auth()->check() && !auth()->user()->is_approved) {
            $allowedRoutes = [
                'waitlist',
                'logout',
                'privacy',
                'terms',
                'cookies',
                'locale.switch',
            ];

            if (!$request->routeIs($allowedRoutes)) {
                return redirect()->route('waitlist');
            }
        }

        return $next($request);
    }
}
