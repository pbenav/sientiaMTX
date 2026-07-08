<?php

namespace App\Http\Controllers\Metrics;

use App\Http\Controllers\Controller;
use App\Services\Metrics\AppointmentMetricsService;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentsDashboardController extends Controller
{
    /**
     * Appointments dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $teamId = $request->input('team_id', $user->favorite_team_id);
        $team = Team::find($teamId);

        $appointments = app(AppointmentMetricsService::class);

        $days = $request->input('days', 30);

        $stats = $appointments->getOverview($days);
        $trends = $appointments->getBookingTrends($days);
        $peakHours = $appointments->getPeakHours($days);
        $peakDays = $appointments->getPeakDays($days);
        $returnRate = $appointments->getReturnRate(90);
        $serviceDistribution = $appointments->getDistributionByService($days);
        $cancellationTrend = $appointments->getCancellationTrend();
        $noShowTrend = $appointments->getNoShowTrend();

        return view('metrics.appointments.dashboard', compact(
            'user', 'team', 'stats', 'trends', 'peakHours', 'peakDays',
            'returnRate', 'serviceDistribution',
            'cancellationTrend', 'noShowTrend', 'days'
        ));
    }
}
