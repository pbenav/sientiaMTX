<?php

namespace App\Http\Controllers\Microsite;

use App\Http\Controllers\Controller;
use App\Models\Microsite;
use Illuminate\Http\Request;

class PublicMicrositeController extends Controller
{
    public function directory(Request $request)
    {
        // El mapa y listado de todos los micrositios públicos
        $query = Microsite::where('is_published', true)
            ->whereHas('team', function ($q) {
                // Solo equipos que tengan habilitado los micrositios
                $q->where('settings->microsites_enabled', true);
            })
            ->with(['team', 'user']);

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%')
                  ->orWhere('city', 'like', '%' . $request->q . '%');
        }

        $microsites = $query->latest()->paginate(20);

        // Micrositios con coordenadas para el mapa
        $mapMicrosites = Microsite::where('is_published', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('team', function ($q) {
                $q->where('settings->microsites_enabled', true);
            })
            ->get();

        return view('microsites.public.directory', compact('microsites', 'mapMicrosites'));
    }

    public function show(string $slug)
    {
        $microsite = Microsite::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Check if the team still allows it
        if (!($microsite->team->settings['microsites_enabled'] ?? false)) {
            abort(404);
        }

        // Increment views
        $microsite->increment('views');

        return view('microsites.public.show', compact('microsite'));
    }
}
