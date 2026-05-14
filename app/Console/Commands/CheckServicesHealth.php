<?php

namespace App\Console\Commands;

use App\Services\SentinelService;
use Illuminate\Console\Command;

class CheckServicesHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentinel:check {service?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform health checks on registered services using Sentinel';

    /**
     * Execute the console command.
     */
    public function handle(SentinelService $sentinel)
    {
        $serviceId = $this->argument('service');

        if ($serviceId) {
            $service = \App\Models\Service::find($serviceId);
            if (!$service) {
                $this->error("Service not found.");
                return 1;
            }
            $this->info("Checking service: {$service->name}...");
            $sentinel->checkService($service);
        } else {
            $this->info("Checking all services...");
            $sentinel->checkAll();
        }

        $this->info("Health check completed.");
        return 0;
    }
}
