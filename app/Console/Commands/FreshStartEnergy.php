<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FreshStartEnergy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:fresh-start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guarantees a minimum energy baseline (80%) for users in the morning.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
            $morningTime = $settings['morning_summary_time'] ?? '08:00';
            
            $siteTimezone = config('app.timezone', 'UTC');
            $userTime = now($user->timezone ?? $siteTimezone);
            
            // Comprobamos si estamos en la hora de inicio del día del usuario
            $currentHour = $userTime->format('H');
            $morningHour = date('H', strtotime($morningTime));

            if ($currentHour === $morningHour) {
                $currentEnergy = $user->energy_level ?? 100;
                
                if ($currentEnergy < 80) {
                    $user->update(['energy_level' => 80]);
                    $count++;
                    Log::info("Fresh Start: Energía restablecida al 80% para {$user->name} (Estaba en {$currentEnergy}%)");
                }
            }
        }

        if ($count > 0) {
            $this->info("Fresh Start completion: Se ha garantizado el mínimo de energía para {$count} usuarios.");
        }
    }
}
