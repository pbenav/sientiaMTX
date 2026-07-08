<?php

namespace App\Console\Commands\Metrics;

use App\Models\MetricAlert;
use App\Models\MetricSnapshot;
use App\Models\MetricReport;
use App\Models\User;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\ProductivityMetricsService;
use App\Services\Metrics\TimeMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Services\Metrics\GamificationMetricsService;
use App\Services\Metrics\AppointmentMetricsService;
use App\Services\Metrics\SurveyMetricsService;
use App\Services\Metrics\ExecutiveMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMetricSnapshots extends Command
{
    protected $signature = 'metrics:snapshots {--daily : Generate daily snapshots only}
                           {--weekly : Generate weekly snapshots}
                           {--monthly : Generate monthly snapshots}
                           {--user= : Generate for specific user ID}
                           {--team= : Generate for specific team ID}';

    protected $description = 'Generate consolidated metric snapshots for all users and teams';

    public function handle(): int
    {
        $this->info('Iniciando generación de snapshots de métricas...');

        $isDaily = $this->option('daily');
        $isWeekly = $this->option('weekly');
        $isMonthly = $this->option('monthly');

        if (!$isDaily && !$isWeekly && !$isMonthly) {
            $isDaily = true;
        }

        $today = now()->toDateString();
        $userQuery = User::with('team')->active();

        if ($userId = $this->option('user')) {
            $userQuery->where('id', $userId);
        }

        if ($teamId = $this->option('team')) {
            $userQuery->where('team_id', $teamId);
        }

        $users = $userQuery->get();
        $totalUsers = $users->count();
        $processed = 0;

        $this->task('Calculando métricas individuales...', function () use ($users, $today, &$processed) {
            $wellnessService = app(WellnessMetricsService::class);
            $productivityService = app(ProductivityMetricsService::class);
            $timeService = app(TimeMetricsService::class);
            $gamificationService = app(GamificationMetricsService::class);

            foreach ($users as $user) {
                try {
                    $wellnessData = $wellnessService->getUserWellness($user->id, $today);
                    $productivityData = $productivityService->getUserProductivity($user->id, $today);
                    $timeData = $timeService->getUserTimeMetrics($user->id, $today);
                    $gamificationData = $gamificationService->getUserGamification($user->id, $today);

                    MetricSnapshot::updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'snapshot_date' => $today,
                            'snapshot_type' => 'daily',
                        ],
                        [
                            'data' => json_encode([
                                'wellness' => $wellnessData,
                                'productivity' => $productivityData,
                                'time' => $timeData,
                                'gamification' => $gamificationData,
                                'burnout_risk' => $wellnessData['burnout_risk'] ?? 'BAJO',
                                'wellness_score' => $wellnessData['wellness_score'] ?? 0,
                                'productivity_score' => $productivityData['productivity_score'] ?? 0,
                                'generated_at' => now()->toISOString(),
                            ]),
                        ]
                    );

                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Error generating snapshot for user {$user->id}: {$e->getMessage()}");
                }
            }
        });

        // Team-level snapshots
        $this->task('Calculando métricas de equipo...', function () use ($today) {
            $teamService = app(TeamMetricsService::class);
            $teams = DB::table('teams')->whereNotNull('id')->get();

            foreach ($teams as $team) {
                try {
                    $teamData = $teamService->getTeamMetrics($team->id, $today);

                    MetricSnapshot::updateOrInsert(
                        [
                            'team_id' => $team->id,
                            'snapshot_date' => $today,
                            'snapshot_type' => 'team_daily',
                        ],
                        [
                            'data' => json_encode($teamData),
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error("Error generating team snapshot for team {$team->id}: {$e->getMessage()}");
                }
            }
        });

        // Weekly aggregations
        if ($isWeekly) {
            $this->task('Generando agregaciones semanales...', function () use ($today) {
                $teamService = app(TeamMetricsService::class);
                $teams = DB::table('teams')->whereNotNull('id')->get();

                foreach ($teams as $team) {
                    try {
                        $weekData = $teamService->getWeeklyTeamMetrics($team->id);

                        MetricSnapshot::updateOrInsert(
                            [
                                'team_id' => $team->id,
                                'snapshot_date' => $today,
                                'snapshot_type' => 'team_weekly',
                            ],
                            [
                                'data' => json_encode($weekData),
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::error("Error generating weekly snapshot for team {$team->id}: {$e->getMessage()}");
                    }
                }
            });
        }

        // Monthly aggregations
        if ($isMonthly) {
            $this->task('Generando agregaciones mensuales...', function () {
                $executiveService = app(ExecutiveMetricsService::class);
                $monthlyData = $executiveService->getOrganizationalMetrics();

                MetricSnapshot::updateOrInsert(
                    [
                        'snapshot_date' => $today,
                        'snapshot_type' => 'org_monthly',
                    ],
                    [
                        'data' => json_encode($monthlyData),
                    ]
                );
            });
        }

        $this->newLine();
        $this->info("Snapshots generados: {$processed} usuarios procesados.");
        $this->info('Snapshot generation complete.');

        return Command::SUCCESS;
    }
}
