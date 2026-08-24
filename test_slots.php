<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;

$start_time = '09:00:00';
$end_time = '10:00:00';
$duration_minutes = 15; // or 30?
$slotMinutes = 15;

$start = Carbon::parse('2023-10-10 ' . $start_time);
$end = Carbon::parse('2023-10-10 ' . $end_time);

$current = $start->copy();
echo "Testing with duration_minutes=$duration_minutes, slotMinutes=$slotMinutes\n";
while ($current->copy()->addMinutes($duration_minutes) <= $end) {
    echo $current->format('H:i') . "\n";
    $current->addMinutes($slotMinutes);
}
