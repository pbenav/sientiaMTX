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
            $newEnergy = min(100, $user->energy_level + 5);
            $user->update(['energy_level' => $newEnergy]);
            $count++;
        }

        $this->info("Regenerada energía para {$count} usuarios.");
    }
}
