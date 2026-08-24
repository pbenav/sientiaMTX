<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AppointmentService;
use App\Services\AppointmentAvailabilityService;
use Carbon\Carbon;

$srv = new AppointmentAvailabilityService();

// Let's test with service ID 3 (user 29, 09:00 - 14:00)
$service = AppointmentService::find(3);
if ($service) {
    echo "Service: {$service->name}, duration: {$service->duration_minutes}\n";
    $date = Carbon::parse('next monday');
    $slots = $srv->getSlotsForDate($service, $date);
    foreach ($slots as $s) {
        echo $s['time'] . " ";
    }
    echo "\n";
}

// Service ID 6 (user 3, 09:00 - 14:00)
$service = AppointmentService::find(6);
if ($service) {
    echo "Service: {$service->name}, duration: {$service->duration_minutes}\n";
    $date = Carbon::parse('next monday');
    $slots = $srv->getSlotsForDate($service, $date);
    foreach ($slots as $s) {
        echo $s['time'] . " ";
    }
    echo "\n";
}
