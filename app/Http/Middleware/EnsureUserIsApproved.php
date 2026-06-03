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
        if (app()->environment('testing')) {
            return $next($request);
        }

        $requireApproval = \App\Models\Setting::get('require_approval', true);

        if ($requireApproval && auth()->check() && !auth()->user()->is_approved) {
            $allowedRoutes = [
                'waitlist',
                'waitlist.*',
                'logout',
                'privacy',
                'terms',
                'cookies',
                'locale.switch',
                'google.*',
                'profile.edit',
                'team.*',
            ];

            $isAllowed = false;
            foreach ($allowedRoutes as $route) {
                if ($request->routeIs($route)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                return redirect()->route('waitlist');
            }
        }

        return $next($request);
    }
}
