<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RegenerateEnergy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:regenerate-energy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerates energy levels for all users over time.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = \App\Models\User::where('energy_level', '<', 100)->get();
        $count = 0;

        foreach ($users as $user) {
            $current = $user->energy_level ?? 100;
            $gain = 5; // Base hourly gain

            // Bonus 1: Recuperación acelerada si estás muy quemado (Ayuda al equipo)
            if ($current < 30) {
                $gain = 15;
            } elseif ($current < 60) {
                $gain = 10;
            }

            // Bonus 2: Modo Descanso (Si es horario nocturno para el usuario)
            if ($user->isInQuietHours()) {
                $gain += 5;
            }

            $newEnergy = min(100, $current + $gain);
            $user->update(['energy_level' => $newEnergy]);
            $count++;
        }

        $this->info("Regenerada energía para {$count} usuarios.");
    }
}
