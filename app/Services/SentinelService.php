<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentinelService
{
    /**
     * Perform a health check on all services with a valid URL.
     */
    public function checkAll()
    {
        $services = Service::whereNotNull('url')
            ->where('url', '!=', '')
            ->get();

        foreach ($services as $service) {
            $this->checkService($service);
        }
    }

    /**
     * Check a single service.
     */
    public function checkService(Service $service)
    {
        $url = $service->url;

        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)
                ->withoutVerifying()
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
     * Handle the result of a check.
     */
    protected function handleResult(Service $service, string $type, string $details)
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
