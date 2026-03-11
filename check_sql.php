<?php

use App\Models\Task;
use App\Models\Team;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$team = Team::first();
$user = User::first();

if (!$team || !$user) {
    die("No team or user found\n");
}

$query = Task::query()->operationalFor($user, $team);
echo $query->toSql() . "\n";
print_r($query->getBindings());
