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
        
        $hasDummyData = false;
        if (($stats['total'] ?? 0) === 0) {
            $hasDummyData = true;
            $stats = [
                'total' => rand(120, 250),
                'confirmed' => rand(80, 150),
                'cancelled' => rand(10, 30),
                'no_show' => rand(5, 20),
                'completed' => rand(25, 50),
            ];
            $stats['total'] = $stats['confirmed'] + $stats['cancelled'] + $stats['no_show'] + $stats['completed'];
            $stats['confirmation_rate'] = round(($stats['confirmed'] / $stats['total']) * 100, 1);
            $stats['cancellation_rate'] = round(($stats['cancelled'] / $stats['total']) * 100, 1);
            $stats['no_show_rate'] = round(($stats['no_show'] / $stats['total']) * 100, 1);
            $stats['completion_rate'] = round(($stats['completed'] / $stats['total']) * 100, 1);
            
            $stats['distribution'] = [
                ['status' => 'confirmed', 'count' => $stats['confirmed']],
                ['status' => 'cancelled', 'count' => $stats['cancelled']],
                ['status' => 'no_show', 'count' => $stats['no_show']],
                ['status' => 'completed', 'count' => $stats['completed']],
            ];

            $trends = [];
            for ($i = 6; $i >= 0; $i--) {
                $trends[] = ['label' => ucfirst(now()->subDays($i)->locale('es')->isoFormat('ddd D')), 'count' => rand(2, 12)];
            }
            $peakHours = array_map(function($h) {
                return ['hour' => $h, 'label' => sprintf('%02d:00', $h), 'count' => rand(0, 15)];
            }, range(0, 23));
            
            $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            $peakDays = [];
            for ($d = 1; $d <= 7; $d++) {
                $peakDays[] = ['day' => $d, 'name' => $dayNames[$d - 1], 'count' => rand(10, 40)];
            }
            
            $returnRate = [
                'total_visitors' => rand(80, 200),
                'returning_visitors' => rand(20, 60),
            ];
            $returnRate['return_rate'] = round(($returnRate['returning_visitors'] / $returnRate['total_visitors']) * 100, 1);
            
            $serviceDistribution = [
                ['service' => 'Consulta General', 'count' => rand(30, 80), 'completed' => rand(15, 30), 'revenue' => rand(1500, 4000)],
                ['service' => 'Revisión', 'count' => rand(20, 50), 'completed' => rand(10, 20), 'revenue' => rand(800, 2000)],
                ['service' => 'Urgencia', 'count' => rand(10, 25), 'completed' => rand(5, 10), 'revenue' => rand(1000, 2500)],
            ];
            
            $cancellationTrend = [];
            $noShowTrend = [];
            for ($i = 7; $i >= 0; $i--) {
                $cancellationTrend[] = [
                    'label' => 'W' . now()->subWeeks($i)->format('W'),
                    'total' => rand(20, 50),
                    'cancelled' => rand(5, 15),
                    'rate' => rand(10, 30)
                ];
                $noShowTrend[] = [
                    'label' => 'W' . now()->subWeeks($i)->format('W'),
                    'total' => rand(20, 50),
                    'no_show' => rand(2, 10),
                    'rate' => rand(5, 20)
                ];
            }
        } else {
            $stats['distribution'] = [
                ['status' => 'confirmed', 'count' => $stats['confirmed']],
                ['status' => 'cancelled', 'count' => $stats['cancelled']],
                ['status' => 'no_show', 'count' => $stats['no_show']],
                ['status' => 'completed', 'count' => $stats['completed']],
            ];
        }

        $confirmationTime = ['avg_hours' => rand(12, 48), 'distribution' => [
            ['label' => '< 1h', 'count' => rand(10, 30)],
            ['label' => '1-6h', 'count' => rand(20, 50)],
            ['label' => '6-24h', 'count' => rand(15, 40)],
            ['label' => '> 24h', 'count' => rand(5, 20)],
        ]];

        $utilization = [];
        for ($i = 0; $i < 7; $i++) {
            $utilization[] = [
                'label' => ucfirst(now()->addDays($i)->locale('es')->isoFormat('ddd D')),
                'used' => rand(10, 35),
                'available' => rand(40, 50),
            ];
        }

        return view('metrics.appointments.dashboard', compact(
            'user', 'team', 'stats', 'trends', 'peakHours', 'peakDays',
            'returnRate', 'serviceDistribution', 'confirmationTime', 'utilization',
            'cancellationTrend', 'noShowTrend', 'days', 'hasDummyData'
        ));
    }
}
