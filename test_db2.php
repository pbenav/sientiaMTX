<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schedules = App\Models\AppointmentSchedule::all();
foreach ($schedules as $s) {
    echo "ID: {$s->id} | User: {$s->user_id} | Srv: {$s->service_id} | Day: {$s->day_of_week} | {$s->start_time} - {$s->end_time} | slot_dur: {$s->slot_duration_minutes}\n";
}
