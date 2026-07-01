<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncWorkdayWithCth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $mtxAction; // 'start' or 'stop'

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $mtxAction)
    {
        $this->user = $user;
        $this->mtxAction = $mtxAction;
    }

    /**
     * Check current status in CTH (Single source of truth).
     */
    public static function checkStatus(User $user): array
    {
        if (!$user->sync_with_cth || !$user->cth_api_token) {
            return ['success' => false, 'is_working' => false];
        }

        return \Illuminate\Support\Facades\Cache::remember('cth_status_' . $user->id, 10, function () use ($user) {
            $apiUrl = rtrim($user->cth_api_url ?: config('services.cth.url'), '/');
            if (str_ends_with($apiUrl, '/api/v1')) {
                $apiUrl = substr($apiUrl, 0, -7);
            } elseif (str_ends_with($apiUrl, '/api')) {
                $apiUrl = substr($apiUrl, 0, -4);
            }
            $token = $user->cth_api_token;

            try {
                $response = Http::timeout(5)->withToken($token)->acceptJson()
                    ->post($apiUrl . '/api/v1/status');

                if ($response->successful()) {
                    $data = $response->json('data');
                    // Si la acción disponible es clock_out o pause, significa que está trabajando en CTH
                    $isWorking = isset($data['action']) && in_array($data['action'], ['clock_out', 'pause']);
                    $startTime = null;
                    $endTime = null;

                    // Auto-healing de códigos si no están en MTX
                    $workCenterCode = $data['current_work_center_code'] ?? null;
                    $needsSave = false;

                    if ($workCenterCode && $user->cth_work_center_code !== $workCenterCode) {
                        $user->cth_work_center_code = $workCenterCode;
                        $needsSave = true;
                    }

                    if (empty($user->cth_user_code)) {
                        try {
                            $profileResponse = Http::timeout(5)->withToken($token)->acceptJson()->get($apiUrl . '/api/v1/profile');
                            if ($profileResponse->successful() && $profileResponse->json('user_code')) {
                                $user->cth_user_code = $profileResponse->json('user_code');
                                $needsSave = true;
                            }
                        } catch (\Exception $e) {
                            // Silencioso
                        }
                    }

                    if ($needsSave) {
                        $user->saveQuietly();
                    }

                    if (!empty($data['today_records'])) {
                        foreach ($data['today_records'] as $record) {
                            // El temporizador en MTX NO se para porque haya fecha de fin en un evento,
                            // sino porque se aplique el check de cerrado en CTH (is_open = false).
                            if (($record['is_open'] ?? false)) {
                                $isWorking = true;
                                $startTime = $record['start'] ?? null;
                                $endTime = null;
                                break;
                            } elseif (!empty($record['end'])) {
                                // Evento con check de cerrado aplicado (is_open = false) y con fecha de fin
                                $startTime = $record['start'] ?? null;
                                $endTime = $record['end'];
                            }
                        }
                    }

                    return [
                        'success' => true,
                        'is_working' => $isWorking,
                        'action' => $data['action'] ?? 'unknown',
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'grace_closing_available' => $data['grace_closing_available'] ?? false,
                        'message' => $response->json('message') ?? null,
                    ];
                }
                return ['success' => false, 'is_working' => false, 'message' => $response->json('message') ?? 'Respuesta fallida de CTH (HTTP ' . $response->status() . ')'];
            } catch (\Exception $e) {
                return ['success' => false, 'is_working' => false, 'message' => 'El servidor CTH no responde. (' . $e->getMessage() . ')'];
            }
        });
    }

    /**
     * Execute the job synchronously and return the result for immediate UI feedback.
     */
    public static function syncNow(User $user, string $mtxAction): array
    {
        if (!$user->sync_with_cth || !$user->cth_api_token) {
            return ['success' => false, 'message' => 'Sincronización CTH desactivada o cuenta no vinculada.'];
        }

        // Invalidar caché inmediatamente para garantizar el reflejo en vivo
        \Illuminate\Support\Facades\Cache::forget('cth_status_' . $user->id);

        $apiUrl = rtrim($user->cth_api_url ?: config('services.cth.url'), '/');
        if (str_ends_with($apiUrl, '/api/v1')) {
            $apiUrl = substr($apiUrl, 0, -7);
        } elseif (str_ends_with($apiUrl, '/api')) {
            $apiUrl = substr($apiUrl, 0, -4);
        }
        $token = $user->cth_api_token;

        if (!$apiUrl || !$token) {
            return ['success' => false, 'message' => 'Falta el token de acceso Sanctum o la URL de CTH.'];
        }

        // SSRF Protection (OWASP A10): Validar URL externa y descartar IPs privadas o AWS Metadata
        $parsedHost = parse_url($apiUrl, PHP_URL_HOST);
        if ($parsedHost) {
            $resolvedIp = gethostbyname($parsedHost);
            if (filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                if (!app()->environment('local', 'development', 'testing')) {
                    Log::error('CTH Sync SSRF Block: Intento de acceso a IP privada o reservada', ['ip' => $resolvedIp, 'host' => $parsedHost]);
                    return ['success' => false, 'message' => 'Acceso bloqueado por protección SSRF (IP Privada).'];
                }
            }
        }

        try {
            // Si la acción es aplicar la medida de gracia, llamamos al endpoint específico de gracia en CTH
            if ($mtxAction === 'grace_closing') {
                $response = Http::timeout(10)->withToken($token)->acceptJson()
                    ->post($apiUrl . '/api/v1/clock/grace-closing');
            } else {
                // Fichaje normal (start/stop) llamando al endpoint estándar de la app móvil
                $payload = [
                    'type' => 'workday',
                    'action' => $mtxAction === 'start' ? 'clock_in' : 'clock_out',
                ];

                if ($user->cth_user_code) {
                    $payload['user_code'] = (string) $user->cth_user_code;
                }
                
                if ($user->cth_work_center_code) {
                    $payload['work_center_code'] = (string) $user->cth_work_center_code;
                }

                $response = Http::timeout(10)->withToken($token)->acceptJson()
                    ->post($apiUrl . '/api/v1/clock', $payload);
            }

            if (!$response->successful()) {
                Log::error('CTH Sync: Failed API call', ['user' => $user->id, 'action' => $mtxAction, 'response' => $response->body()]);
                $msg = $response->json('message') ?: 'El servidor CTH rechazó la petición (HTTP ' . $response->status() . ')';
                $graceAvailable = $response->json('data.grace_closing_available') ?? $response->json('grace_closing_available') ?? false;
                $status = $response->json('status_code') ?? $response->json('status') ?? 'error';
                
                // Interceptar error de exceso de jornada laboral
                if (
                    in_array($status, ['MAX_WORKED_HOURS', 'MAX_HOURS_EXCEEDED']) || 
                    str_contains(strtolower($msg), 'maximum worked hours') || 
                    str_contains(strtolower($msg), 'exceso de jornada')
                ) {
                    $graceAvailable = true;
                    $msg = __('Exceso de jornada laboral superado. Se requiere cerrar los turnos anteriores como excepción.');
                }
                
                if ($mtxAction !== 'grace_closing' && !$graceAvailable) {
                    $user->notify(new \App\Notifications\CthSyncFailedNotification($mtxAction, $msg));
                }
                return ['success' => false, 'message' => $msg, 'grace_closing_available' => $graceAvailable, 'status' => $status];
            }

            Log::info('CTH Sync: Successfully executed ' . $mtxAction . ' via Sanctum API', ['user' => $user->id]);
            return ['success' => true, 'message' => $response->json('message') ?: 'Sincronizado con éxito en CTH.'];
        } catch (\Exception $e) {
            Log::error('CTH Sync Exception: ' . $e->getMessage(), ['user' => $user->id]);
            if ($mtxAction !== 'grace_closing') {
                $user->notify(new \App\Notifications\CthSyncFailedNotification($mtxAction, 'El servidor CTH no está disponible (' . $e->getMessage() . ')'));
            }
            return ['success' => false, 'message' => 'El servidor CTH no responde o no está disponible.'];
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        self::syncNow($this->user, $this->mtxAction);
    }
}
