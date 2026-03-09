<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check authenticated user's saved locale preference
        if (auth()->check() && auth()->user()->locale) {
            App::setLocale(auth()->user()->locale);
            session(['locale' => auth()->user()->locale]);
        }
        // 2. Check session
        elseif (session()->has('locale')) {
            App::setLocale(session('locale'));
        }
        // 3. Try to detect from browser Accept-Language header
        else {
            $browserLocale = substr($request->getPreferredLanguage(['en', 'es']), 0, 2);
            App::setLocale($browserLocale ?: config('app.locale'));
        }

        return $next($request);
    }
}
