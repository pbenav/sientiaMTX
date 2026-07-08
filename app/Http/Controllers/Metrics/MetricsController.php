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

class MetricsController extends Controller
{
    public function index()
    {
        return redirect()->route('metrics.personal.daily');
    }
}
