<?php

namespace App\Console\Commands\Metrics;

use App\Models\MetricAlert;
use App\Models\MetricSnapshot;
use App\Models\User;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\ProductivityMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Services\Metrics\AppointmentMetricsService;
use App\Services\Metrics\SurveyMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckMetricAlerts extends Command
{
    protected $signature = 'metrics:check-alerts {--force : Force alert checking even if recently run}';
    protected $description = 'Check for metric thresholds and generate proactive alerts';

    public function handle(): int
    {
        $this->info('Verificando alertas de métricas...');

        $wellnessService = app(WellnessMetricsService::class);
        $productivityService = app(ProductivityMetricsService::class);
        $teamService = app(TeamMetricsService::class);
        $appointmentService = app(AppointmentMetricsService::class);
        $surveyService = app(SurveyMetricsService::class);

        $alertsGenerated = 0;

        // 1. Burnout risk alerts
        $this->task('Verificando riesgo de burnout...', function () use ($wellnessService, &$alertsGenerated) {
            $burnoutUsers = $wellnessService->getBurnoutRiskByTeam();

            foreach ($burnoutUsers as $risk) {
                if ($risk['risk_level'] === 'ALTO') {
                    $existing = MetricAlert::where('user_id', $risk['user_id'])
                        ->where('alert_type', 'burnout_risk_high')
                        ->where('is_resolved', false)
                        ->first();

                    if (!$existing) {
                        MetricAlert::create([
                            'user_id' => $risk['user_id'],
                            'alert_type' => 'burnout_risk_high',
                            'severity' => 'critical',
                            'title' => 'Riesgo de Burnout Alto Detectado',
                            'message' => "El usuario {$risk['user_name']} presenta un riesgo de burnout alto (Wellness: {$risk['wellness_score']}, Estrés: {$risk['stress_score']}). Se recomienda intervención inmediata.",
                            'data' => json_encode($risk),
                            'is_read' => false,
                            'is_resolved' => false,
                        ]);
                        $alertsGenerated++;
                    }
                }
            }
        });

        // 2. Prolonged high stress alerts
        $this->task('Verificando estrés prolongado...', function () use ($wellnessService, &$alertsGenerated) {
            $highStressUsers = $wellnessService->getHighStressUsers(5);

            foreach ($highStressUsers as $user) {
                if ($user['stress_score'] > 75 && $user['consecutive_days'] >= 5) {
                    $existing = MetricAlert::where('user_id', $user['user_id'])
                        ->where('alert_type', 'prolonged_high_stress')
                        ->where('is_resolved', false)
                        ->first();

                    if (!$existing) {
                        MetricAlert::create([
                            'user_id' => $user['user_id'],
                            'alert_type' => 'prolonged_high_stress',
                            'severity' => 'high',
                            'title' => 'Estrés Elevado Prolongado',
                            'message' => "El usuario {$user['user_name']} ha mantenido estrés alto (>75) por {$user['consecutive_days']} días consecutivos.",
                            'data' => json_encode($user),
                            'is_read' => false,
                            'is_resolved' => false,
                        ]);
                        $alertsGenerated++;
                    }
                }
            }
        });

        // 3. Excessive overtime alerts
        $this->task('Verificando horas extra excesivas...', function () use ($wellnessService, &$alertsGenerated) {
            $overtimeUsers = $wellnessService->getTeamOvertimeTracking(now()->startOfWeek(), now()->endOfWeek());

            foreach ($overtimeUsers as $user) {
                if ($user['overtime_hours'] > 10) {
                    $existing = MetricAlert::where('user_id', $user['user_id'])
                        ->where('alert_type', 'excessive_overtime')
                        ->where('is_resolved', false)
                        ->where('snapshot_date', now()->toDateString())
                        ->first();

                    if (!$existing) {
                        MetricAlert::create([
                            'user_id' => $user['user_id'],
                            'alert_type' => 'excessive_overtime',
                            'severity' => 'high',
                            'title' => 'Horas Extra Excesivas',
                            'message' => "El usuario {$user['user_name']} ha trabajado {$user['overtime_hours']} horas extra esta semana (límite: 10h).",
                            'data' => json_encode($user),
                            'is_read' => false,
                            'is_resolved' => false,
                        ]);
                        $alertsGenerated++;
                    }
                }
            }
        });

        // 4. Team load imbalance alerts
        $this->task('Verificando desbalance de carga...', function () use ($teamService, &$alertsGenerated) {
            $teams = DB::table('teams')->whereNotNull('id')->get();

            foreach ($teams as $team) {
                $imbalance = $teamService->getLoadDistribution($team->id);
                if ($imbalance['coefficient_of_variation'] > 50) {
                    $existing = MetricAlert::where('team_id', $team->id)
                        ->where('alert_type', 'load_imbalance')
                        ->where('is_resolved', false)
                        ->where('snapshot_date', now()->toDateString())
                        ->first();

                    if (!$existing) {
                        MetricAlert::create([
                            'team_id' => $team->id,
                            'alert_type' => 'load_imbalance',
                            'severity' => 'medium',
                            'title' => 'Desbalance de Carga en Equipo',
                            'message' => "El equipo {$team->name} presenta un coeficiente de variación de {$imbalance['coefficient_of_variation']}%. Se recomienda redistribuir tareas.",
                            'data' => json_encode($imbalance),
                            'is_read' => false,
                            'is_resolved' => false,
                        ]);
                        $alertsGenerated++;
                    }
                }
            }
        });

        // 5. Bottleneck alerts
        $this->task('Verificando cuellos de botella...', function () use ($teamService, &$alertsGenerated) {
            $teams = DB::table('teams')->whereNotNull('id')->get();

            foreach ($teams as $team) {
                $bottlenecks = $teamService->getBottlenecks($team->id, 5);

                foreach ($bottlenecks as $bottleneck) {
                    if ($bottleneck['days_stuck'] >= 5) {
                        $existing = MetricAlert::where('team_id', $team->id)
                            ->where('alert_type', 'prolonged_bottleneck')
                            ->where('is_resolved', false)
                            ->where('snapshot_date', now()->toDateString())
                            ->first();

                        if (!$existing) {
                            MetricAlert::create([
                                'team_id' => $team->id,
                                'alert_type' => 'prolonged_bottleneck',
                                'severity' => 'medium',
                                'title' => 'Cuello de Botella Prolongado',
                                'message' => "Actividad '{$bottleneck['title']}' lleva {$bottleneck['days_stuck']} días estancada en estado '{$bottleneck['status']}'. Asignado a: {$bottleneck['assignee']}.",
                                'data' => json_encode($bottleneck),
                                'is_read' => false,
                                'is_resolved' => false,
                            ]);
                            $alertsGenerated++;
                        }
                    }
                }
            }
        });

        // 6. Appointment no-show rate alerts
        $this->task('Verificando tasa de no-show de citas...', function () use ($appointmentService, &$alertsGenerated) {
            $appointmentStats = $appointmentService->getAppointmentStats(7);

            if ($appointmentStats['no_show_rate'] > 20) {
                $existing = MetricAlert::where('alert_type', 'high_no_show_rate')
                    ->where('is_resolved', false)
                    ->where('snapshot_date', now()->toDateString())
                    ->first();

                if (!$existing) {
                    MetricAlert::create([
                        'alert_type' => 'high_no_show_rate',
                        'severity' => 'high',
                        'title' => 'Tasa de No-Show Elevada',
                        'message' => "La tasa de no-show de citas alcanzó {$appointmentStats['no_show_rate']}% en los últimos 7 días (umbral: 20%).",
                        'data' => json_encode($appointmentStats),
                        'is_read' => false,
                        'is_resolved' => false,
                    ]);
                    $alertsGenerated++;
                }
            }
        });

        $this->newLine();
        $this->info("Verificación completada. {$alertsGenerated} alertas generadas.");

        return Command::SUCCESS;
    }
}
