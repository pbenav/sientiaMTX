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
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->user->sync_with_cth) {
            return;
        }

        // Use global S2S config for transparent sync
        $apiUrl = rtrim(config('services.cth.url'), '/');
        $secret = config('services.cth.secret');
        
        if (!$apiUrl || !$secret) {
            Log::warning('CTH Sync: Missing S2S configuration in .env');
            return;
        }

        // SSRF Protection (OWASP A10): Validar URL externa y descartar IPs privadas o AWS Metadata
        $parsedHost = parse_url($apiUrl, PHP_URL_HOST);
        if ($parsedHost) {
            $resolvedIp = gethostbyname($parsedHost);
            if (filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                // Permitir solo si estamos en entorno de desarrollo/local
                if (!app()->environment('local', 'development', 'testing')) {
                    Log::error('CTH Sync SSRF Block: Intento de acceso a IP privada o reservada', ['ip' => $resolvedIp, 'host' => $parsedHost]);
                    return;
                }
            }
        }

        try {
            $clockResponse = Http::withHeaders(['X-S2S-Secret' => $secret])
                ->acceptJson()
                ->post($apiUrl . '/api/s2s/sync-workday', [
                    'email' => $this->user->email,
                    'action' => $this->mtxAction,
                ]);

            if (!$clockResponse->successful()) {
                Log::error('CTH Sync: Failed to clock in CTH via S2S', ['user' => $this->user->id, 'response' => $clockResponse->body()]);
                $this->user->notify(new \App\Notifications\CthSyncFailedNotification($this->mtxAction, 'El servidor CTH rechazó la petición'));
            } else {
                Log::info('CTH Sync: Successfully synced ' . $this->mtxAction . ' to CTH via S2S', ['user' => $this->user->id]);
            }
        } catch (\Exception $e) {
            Log::error('CTH Sync Exception: ' . $e->getMessage(), ['user' => $this->user->id]);
            $this->user->notify(new \App\Notifications\CthSyncFailedNotification($this->mtxAction, 'El servidor CTH no está disponible'));
        }
    }
}
