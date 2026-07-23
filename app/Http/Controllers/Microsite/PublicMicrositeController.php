<?php

namespace App\Http\Controllers\Microsite;

use App\Http\Controllers\Controller;
use App\Models\Microsite;
use App\Services\Microsite\MicrositeContentService;
use Illuminate\Http\Request;

/**
 * Controlador público para la visualización de micrositios accesibles sin autenticación.
 *
 * Proporciona un directorio de micrositios públicos con búsqueda y filtrado por equipo,
 * así como la visualización individual de cada micrositio con procesamiento de contenido
 * HTML/CSS a través de MicrositeContentService. Contabiliza las visitas incrementando
 * el contador de vistas.
 *
 * Rutas asociadas:
 *   - GET /microsites/directory
 *   - GET /microsites/{slug}
 */
class PublicMicrositeController extends Controller
{
    /**
     * Muestra el directorio público de todos los micrositios publicados.
     *
     * Incluye la lista paginada con búsqueda por título y ciudad, y los micrositios
     * con coordenadas para visualización en mapa.
     *
     * @param Request $request Parámetro opcional 'q' para búsqueda
     * @return \Illuminate\View\View
     */
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
            ->with('team')
            ->get();

        return view('microsites.public.directory', compact('microsites', 'mapMicrosites'));
    }

    /**
     * Muestra un micrositio público individual por su slug.
     *
     * Verifica que el micrositio esté publicado y que el equipo aún tenga habilitada
     * la función de micrositios. Incrementa el contador de vistas y procesa el contenido
     * HTML/CSS mediante MicrositeContentService.
     *
     * @param string $slug Identificador único del micrositio
     * @return \Illuminate\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
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

        $prepared = app(MicrositeContentService::class)->prepareMicrosite($microsite);
        $htmlContent = $prepared['html'];
        $cssContent = $prepared['css'];
        $usesTailwind = $prepared['uses_tailwind'];

        return view('microsites.public.show', compact('microsite', 'htmlContent', 'cssContent', 'usesTailwind'));
    }
}
