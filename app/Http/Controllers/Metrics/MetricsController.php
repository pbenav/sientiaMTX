<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\ProductivityMetricsService;
use App\Services\Metrics\GamificationMetricsService;
use App\Services\Metrics\TimeMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Models\MetricAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador principal del módulo de métricas y analíticas del panel de gestión.
 *
 * Actúa como punto de entrada para la vista general de métricas que integra datos de
 * bienestar, productividad, gamificación, tiempo y análisis de equipos.
 *
 * Ruta asociada: GET /metrics
 */
class MetricsController extends Controller
{
    /**
     * Muestra la vista principal del panel de métricas.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('metrics.index');
    }
}
