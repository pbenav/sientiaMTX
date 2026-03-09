<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    /**
     * Available locales for the application.
     */
    protected array $availableLocales = ['en', 'es'];

    /**
     * Switch the application locale.
     */
    public function switch(Request $request, string $locale)
    {
        if (!in_array($locale, $this->availableLocales)) {
            abort(400, 'Unsupported locale');
        }

        session(['locale' => $locale]);

        // If user is authenticated, save their preference
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        return redirect()->back()->withHeaders([
            'Cache-Control' => 'no-store',
        ]);
    }
}
