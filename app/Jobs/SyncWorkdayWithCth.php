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
        if (!$this->user->cth_api_url || !$this->user->cth_api_token) {
            return;
        }

        $apiUrl = rtrim($this->user->cth_api_url, '/');
        
        try {
            // First, get the current status in CTH
            $statusResponse = Http::withToken($this->user->cth_api_token)
                ->acceptJson()
                ->post($apiUrl . '/api/status', [
                    'user_code' => $this->user->cth_user_code,
                    'work_center_code' => $this->user->cth_work_center_code,
                ]);

            if (!$statusResponse->successful()) {
                Log::warning('CTH Sync: Failed to get status from CTH', ['user' => $this->user->id, 'response' => $statusResponse->body()]);
                return;
            }

            $cthData = $statusResponse->json('data');
            $cthNextAction = $cthData['action'] ?? null;
            $cthCanClock = $cthData['can_clock'] ?? false;

            // Determine if we need to hit the /api/clock endpoint
            $shouldClock = false;
            $requestPayload = [
                'user_code' => $this->user->cth_user_code,
                'work_center_code' => $this->user->cth_work_center_code,
            ];

            if ($this->mtxAction === 'start') {
                // If MTX wants to start, and CTH next action is 'clock_in' or 'confirm_exceptional_clock_in'
                if (in_array($cthNextAction, ['clock_in', 'confirm_exceptional_clock_in']) && $cthCanClock) {
                    $shouldClock = true;
                }
            } elseif ($this->mtxAction === 'stop') {
                // If MTX wants to stop, and CTH next action is 'clock_out' or 'working_options'
                if (in_array($cthNextAction, ['clock_out', 'working_options'])) {
                    $shouldClock = true;
                }
            }

            if ($shouldClock) {
                $clockResponse = Http::withToken($this->user->cth_api_token)
                    ->acceptJson()
                    ->post($apiUrl . '/api/clock', $requestPayload);

                if (!$clockResponse->successful()) {
                    Log::error('CTH Sync: Failed to clock in CTH', ['user' => $this->user->id, 'response' => $clockResponse->body()]);
                } else {
                    Log::info('CTH Sync: Successfully synced ' . $this->mtxAction . ' to CTH', ['user' => $this->user->id]);
                }
            } else {
                Log::info('CTH Sync: Skipped sync because CTH is already in the desired state', ['user' => $this->user->id, 'mtx_action' => $this->mtxAction, 'cth_action' => $cthNextAction]);
            }

        } catch (\Exception $e) {
            Log::error('CTH Sync Exception: ' . $e->getMessage(), ['user' => $this->user->id]);
        }
    }
}
