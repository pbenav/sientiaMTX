<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de monitoreo de salud de servicios externos.
 *
 * Realiza health checks periódicos a servicios con URL configurada,
 * registra resultados en ServiceReport y actualiza el estado del servicio.
 */
class SentinelService
{
    /**
     * Realiza health check a todos los servicios con URL válida.
     *
     * @return void
     */
    public function checkAll(): void
    {
        $services = Service::whereNotNull('url')
            ->where('url', '!=', '')
            ->get();

        foreach ($services as $service) {
            $this->checkService($service);
        }
    }

    /**
     * Realiza health check a un servicio individual.
     *
     * Envía petición GET con User-Agent personalizado y timeout de 10s.
     * Si recibe 401/403 considera el servicio "up" (servidor responde).
     * Registra el resultado y actualiza el estado si cambió.
     *
     * @param  Service  $service
     * @return void
     */
    public function checkService(Service $service): void
    {
        $url = $service->url;

        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Sientia Sentinel/1.0 (Health Checker)'
                ])
                ->get($url);
            $duration = microtime(true) - $startTime;

            $status = $response->status();
            
            if ($response->successful()) {
                $this->handleResult($service, 'up', "Respuesta exitosa ({$status}) en " . round($duration * 1000) . "ms");
            } elseif ($status === 403 || $status === 401) {
                $this->handleResult($service, 'up', "Acceso restringido ({$status}), pero el servidor responde.");
            } else {
                $this->handleResult($service, 'down', "Error HTTP {$status} detectado automáticamente.");
            }
        } catch (\Exception $e) {
            $this->handleResult($service, 'down', "Fallo de conexión: " . $e->getMessage());
        }
    }

    /**
     * Maneja el resultado de un health check.
     *
     * Evita duplicados idénticos si el estado no cambió en los últimos 30 minutos.
     * Crea un ServiceReport y actualiza el estado del servicio si es necesario.
     *
     * @param  Service  $service
     * @param  string  $type  'up' o 'down'
     * @param  string  $details
     * @return void
     */
    protected function handleResult(Service $service, string $type, string $details): void
    {
        $lastReport = $service->reports()
            ->where('user_id', null)
            ->latest()
            ->first();

        // Evitar duplicados idénticos si el estado no ha cambiado significativamente
        if ($lastReport && $lastReport->type === $type && $lastReport->created_at > now()->subMinutes(30)) {
            return;
        }

        ServiceReport::create([
            'service_id' => $service->id,
            'user_id' => null, // Sistema
            'type' => $type,
            'details' => $details,
            'is_verified' => true
        ]);

        // Actualizar el estado del servicio si es necesario
        if ($service->status !== $type) {
            $service->update([
                'status' => $type,
                'status_updated_at' => now()
            ]);
            
            Log::info("Sentinel: Servicio '{$service->name}' cambió a estado {$type}.");
        }
    }
}
