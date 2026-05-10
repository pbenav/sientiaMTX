<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\ServiceReport;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

class CheckSentinelServicesCommand extends Command
{
    protected $signature = 'app:check-sentinel {--force : Run all checks bypassing intervals}';
    protected $description = 'Automatically verify status and latency of all Sentinel configured services.';

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

    private function performCheck(Service $service)
    {
        $startTime = microtime(true);
        $status = 'down';
        $latency = 0;
        $details = null;

        try {
            // Set a timeout explicitly so it doesn't hang the scheduler
            $response = Http::timeout(10)
                            ->connectTimeout(5)
                            ->get($service->url);
            
            $endTime = microtime(true);
            $latency = (int)(($endTime - $startTime) * 1000); // Convert to milliseconds

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

        $newServiceStatus = $status;

        // HUMAN VETO Logic: If a human recently reported down, we stay 'unstable' at best until humans say OK, even if robot likes it
        if ($newServiceStatus === 'up' && $recentHumanKo) {
            $newServiceStatus = 'unstable'; 
            $this->line("   -> <comment>Upgraded to UP by bot, but RESTRICTED to Unstable by recent HUMAN VETO.</comment>");
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
