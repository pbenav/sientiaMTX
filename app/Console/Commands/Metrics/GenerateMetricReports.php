<?php

namespace App\Console\Commands\Metrics;

use App\Models\MetricAlert;
use App\Models\MetricReport;
use App\Services\Metrics\ExecutiveMetricsService;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\ProductivityMetricsService;
use App\Services\Metrics\AppointmentMetricsService;
use App\Services\Metrics\TeamMetricsService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMetricReports extends Command
{
    protected $signature = 'metrics:reports {--daily : Generate daily reports}
                           {--weekly : Generate weekly reports}
                           {--monthly : Generate monthly reports}
                           {--executive : Generate executive report}
                           {--wellness : Generate wellness report}
                           {--productivity : Generate productivity report}';

    protected $description = 'Generate automated metric reports';

    public function handle(): int
    {
        $this->info('Generando reportes de métricas...');

        $reportType = 'daily';
        $isExecutive = $this->option('executive');
        $isWellness = $this->option('wellness');
        $isProductivity = $this->option('productivity');

        if ($this->option('weekly')) {
            $reportType = 'weekly';
        } elseif ($this->option('monthly')) {
            $reportType = 'monthly';
        }

        try {
            if ($isExecutive || $isWellness || $isProductivity) {
                $reportData = [];

                if ($isExecutive) {
                    $executiveService = app(ExecutiveMetricsService::class);
                    $reportData = $executiveService->getExecutiveDashboard(now());
                    $reportData['report_type'] = 'executive';
                }

                if ($isWellness) {
                    $wellnessService = app(WellnessMetricsService::class);
                    $reportData['wellness'] = $wellnessService->getOrganizationalWellness(now());
                    $reportData['report_type'] = 'wellness';
                }

                if ($isProductivity) {
                    $productivityService = app(ProductivityMetricsService::class);
                    $reportData['productivity'] = $productivityService->getTeamProductivityOverview(now());
                    $reportData['report_type'] = 'productivity';
                }
            } else {
                // Default: generate comprehensive daily report
                $executiveService = app(ExecutiveMetricsService::class);
                $reportData = $executiveService->getExecutiveDashboard(now());
                $reportData['report_type'] = $reportType;
            }

            $report = MetricReport::create([
                'report_type' => $reportData['report_type'] ?? $reportType,
                'period_start' => now()->startOfDay(),
                'period_end' => now()->endOfDay(),
                'data' => json_encode($reportData),
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            $this->info("Reporte generado exitosamente (ID: {$report->id})");

            // Send notifications for critical alerts
            if (!empty($reportData['critical_alerts'])) {
                foreach ($reportData['critical_alerts'] as $alert) {
                    MetricAlert::create([
                        'user_id' => $alert['user_id'] ?? null,
                        'team_id' => $alert['team_id'] ?? null,
                        'alert_type' => $alert['type'] ?? 'general',
                        'severity' => $alert['severity'] ?? 'medium',
                        'title' => $alert['title'] ?? 'Alerta de métricas',
                        'message' => $alert['message'] ?? '',
                        'data' => json_encode($alert),
                        'is_read' => false,
                        'is_resolved' => false,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error generating metric report: {$e->getMessage()}");
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
