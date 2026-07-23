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

/**
 * Genera reportes automatizados de métricas organizacionales.
 *
 * Crea reportes consolidados de datos de bienestar, productividad y ejecutivo
 * mediante servicios especializados. Soporta reportes diarios, semanales,
 * mensuales y especializados por tipo. Genera alertas automáticas ante
 * eventos críticos detectados en los datos.
 *
 * # Ejecución
 * ```bash
 * php artisan metrics:reports --daily
 * php artisan metrics:reports --weekly
 * php artisan metrics:reports --monthly
 * php artisan metrics:reports --executive
 * php artisan metrics:reports --wellness
 * php artisan metrics:reports --productivity
 * ```
 *
 * @author  SientiaMTX Team
 * @version 1.0.0
 */
class GenerateMetricReports extends Command
{
    /**
     * Firma del comando con múltiples opciones de tipo de reporte.
     *
     * --daily     : Genera reportes diarios (comportamiento por defecto).
     * --weekly    : Genera reportes semanales.
     * --monthly   : Genera reportes mensuales.
     * --executive : Genera reporte ejecutivo consolidado.
     * --wellness  : Genera reporte de bienestar organizacional.
     * --productivity : Genera reporte de productividad del equipo.
     */
    protected $signature = 'metrics:reports {--daily : Generate daily reports}
                           {--weekly : Generate weekly reports}
                           {--monthly : Generate monthly reports}
                           {--executive : Generate executive report}
                           {--wellness : Generate wellness report}
                           {--productivity : Generate productivity report}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Generate automated metric reports';

    /**
     * Punto de entrada principal del comando.
     *
     * Determina el tipo de reporte según las opciones proporcionadas, delega en los
     * servicios especializados (ExecutiveMetricsService, WellnessMetricsService,
     * ProductivityMetricsService) para obtener los datos, persiste el reporte en
     * MetricReport y genera alertas automáticas para eventos críticos.
     *
     * @return int Código de salida del comando (SUCCESS o FAILURE).
     */
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
