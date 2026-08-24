<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AppointmentService;

$services = AppointmentService::all();
foreach ($services as $s) {
    echo "ID: {$s->id} | Name: {$s->name} | duration: {$s->duration_minutes} | slot_duration: {$s->slot_duration_minutes}\n";
    $settings = $s->user->appointmentSettings()->first();
    echo "  User Default Slot: " . ($settings ? $settings->default_slot_duration : 'None') . "\n";
}
