<?php

namespace App\Console\Commands\Gamification;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\Gamification\BadgeService;

class EvaluateBadges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:evaluate-badges {user? : ID of the specific user to evaluate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluates all users (or a specific user) and retrospectively awards any missing gamification badges they qualify for.';

    /**
     * Execute the console command.
     */
    public function handle(BadgeService $badgeService)
    {
        $userId = $this->argument('user');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            $users = collect([$user]);
            $this->info("Evaluating badges for user: {$user->name}");
        } else {
            $users = User::all();
            $this->info("Evaluating badges for all {$users->count()} users...");
        }

        $bar = $this->output->createProgressBar(count($users));
        $bar->start();

        foreach ($users as $user) {
            $team = $user->teams()->first();
            $badgeService->evaluate($user, $team ? $team->id : null);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Badge evaluation complete!');

        return 0;
    }
}
