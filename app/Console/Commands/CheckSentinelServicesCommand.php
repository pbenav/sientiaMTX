<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\ServiceReport;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

/**
 * Comando de verificación automática del estado y latencia de servicios configurados en Sentinel.
 *
 * Evalúa periódicamente la disponibilidad de cada servicio mediante peticiones HTTP,
 * calcula la latencia de respuesta, valida palabras clave opcionales en el contenido
 * y aplica un sistema de veto humano que prioriza reportes manuales recientes.
 *
 * # Ejecución
 * ```bash
 * php artisan app:check-sentinel
 * php artisan app:check-sentinel --force
 * ```
 *
 * @author  SientiaMTX Team
 * @version 1.0.0
 */
class CheckSentinelServicesCommand extends Command
{
    /**
     * Firma del comando con soporte para ejecución forzada.
     *
     * --force : Ejecuta todas las verificaciones ignorando los intervalos inteligentes.
     */
    protected $signature = 'app:check-sentinel {--force : Run all checks bypassing intervals}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Automatically verify status and latency of all Sentinel configured services.';

    /**
     * Punto de entrada principal del comando.
     *
     * Itera sobre todos los servicios con URL configurada, determina si necesitan verificación
     * según su estado actual y el intervalo inteligente (backoff strategy), y ejecuta la
     * comprobación HTTP correspondiente.
     *
     * @return int Código de salida del comando (SUCCESS o FAILURE).
     */
    public function handle()
    {
        $this->info("--- Starting Sentinel Automated Guard ---");
        $services = Service::whereNotNull('url')->get();
        $count = 0;

        foreach ($services as $service) {
            // 1. Calculate smart interval with Backoff strategy
            $baseInterval = $service->check_interval_minutes ?? 15;
            $currentInterval = $baseInterval;

            // If down, back off to 30 mins to avoid spamming.
            if ($service->status === 'down') {
                $currentInterval = max($baseInterval, 30); 
            } elseif ($service->status === 'unstable') {
                // If unstable, check more frequently to detect fast recovery? 
                // Let's stick to 5 minutes to maintain awareness without hammer.
                $currentInterval = min($baseInterval, 5);
            }

            $needsCheck = $this->option('force') || 
                         !$service->last_checked_at || 
                         Carbon::parse($service->last_checked_at)->addMinutes($currentInterval)->isPast();

            if (!$needsCheck) {
                continue;
            }

            $this->line("Checking: <info>{$service->name}</info> ({$service->url})");
            $this->performCheck($service);
            $count++;
        }

        $this->info("--- Sentinel Run Complete. Evaluated {$count} services. ---");
        return Command::SUCCESS;
    }

    /**
     * Ejecuta la verificación HTTP completa para un servicio y actualiza su estado.
     *
     * Realiza una petición HTTP GET con timeout configurado, calcula la latencia,
     * valida palabras clave opcionales en el cuerpo de respuesta, verifica el límite
     * de latencia máxima y aplica la lógica de veto humano. Actualiza el modelo de
     * servicio y registra un reporte automático en la base de datos.
     *
     * @param Service $service El modelo de servicio a verificar.
     * @throws \Exception Propagado si falla la conexión HTTP.
     */
    private function performCheck(Service $service)
    {
        $startTime = microtime(true);
        $status = 'down';
        $latency = 0;
        $details = null;

        try {
            // Disable SSL verification to avoid false positives
            $response = Http::timeout(10)
                            ->connectTimeout(5)
                            ->withoutVerifying()
                            ->get($service->url);
            
            $endTime = microtime(true);
            $latency = (int)(($endTime - $startTime) * 1000); 

            if ($response->successful()) {
                $status = 'up';
                
                // Check Keyword condition if defined
                if (!empty($service->expected_text)) {
                    if (!str_contains(mb_strtolower($response->body()), mb_strtolower($service->expected_text))) {
                        $status = 'unstable';
                        $details = "Success code 200, but required keyword '{$service->expected_text}' was not found in HTML content.";
                    }
                }

                // Check Latency limit (Saturation Detector)
                if ($status === 'up' && $latency > ($service->max_latency_ms ?? 5000)) {
                    $status = 'unstable';
                    $details = "Server responded but is SATURATED ({$latency}ms exceeds limit of {$service->max_latency_ms}ms).";
                }
            } else {
                $status = 'unstable';
                $details = "Server responded with non-success HTTP code: " . $response->status();
            }
        } catch (Exception $e) {
            $status = 'down';
            $details = "Connection failed: " . $e->getMessage();
            $endTime = microtime(true);
            $latency = (int)(($endTime - $startTime) * 1000);
        }

        // Update the model with the latest audit trail
        $service->update([
            'last_checked_at' => now(),
        ]);

        // Save automatic report entry (User ID NULL means system)
        ServiceReport::create([
            'service_id' => $service->id,
            'user_id' => null, 
            'type' => ($status === 'up') ? 'up' : 'down', // map to ENUM
            'latency_ms' => $latency,
            'details' => $details,
            'is_verified' => true // Automated reports are pre-verified
        ]);

        // DECISION ENGINE: 
        // We evaluate the LATEST active human report to see current sentiment.
        $latestHumanReport = $service->reports()
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at', 'desc')
            ->first();

        $recentHumanKo = ($latestHumanReport && $latestHumanReport->type === 'down');
        $recentHumanOk = ($latestHumanReport && $latestHumanReport->type === 'up');

        $newServiceStatus = $status;

        // HUMAN VETO Logic (DOWN): If a human recently reported down, we stay 'unstable' at best until humans say OK, even if robot likes it
        if ($newServiceStatus === 'up' && $recentHumanKo) {
            $newServiceStatus = 'unstable'; 
            $this->line("   -> <comment>Upgraded to UP by bot, but RESTRICTED to Unstable by recent HUMAN VETO (Down).</comment>");
        }

        // HUMAN VETO Logic (UP): If a human recently reported UP, we respect it and ignore bot's 'down' (prevents false negatives)
        if ($newServiceStatus !== 'up' && $recentHumanOk) {
            $newServiceStatus = 'up';
            $this->line("   -> <comment>Downgraded by bot, but OVERRIDDEN to UP by recent HUMAN VETO (Ok).</comment>");
        }

        if ($service->status !== $newServiceStatus) {
            $service->update([
                'status' => $newServiceStatus,
                'status_updated_at' => now()
            ]);
            $this->line("   -> STATUS CHANGED: <fg=yellow>{$service->status}</>");
        } else {
            $this->line("   -> Status remains: {$newServiceStatus} (Latency: {$latency}ms)");
        }
    }
}
