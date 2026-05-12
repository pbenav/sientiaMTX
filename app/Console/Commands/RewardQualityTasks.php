<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Traits\AwardsGamification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RewardQualityTasks extends Command
{
    use AwardsGamification;

    protected $signature = 'task:reward-quality';
    protected $description = 'Processes past-due tasks and awards XP bonus to creators based on quality ratings.';

    public function handle()
    {
        $now = now();
        $this->info("Processing task quality rewards...");

        // Find tasks that passed their due date and haven't had reward processed yet
        $tasks = Task::where('quality_reward_issued', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $now)
            ->with(['creator', 'ratings'])
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            DB::transaction(function () use ($task, &$count) {
                // Recalculate final average just in case cache is stale
                $avgScore = $task->ratings()->avg('score') ?: 0;
                
                if ($avgScore > 0) {
                    // Sync finalized cache
                    $task->avg_quality_score = $avgScore;
                    
                    // Issue the reward logic stored in Trait
                    $this->awardTaskQualityBonus($task, $avgScore);
                }

                // Mark as processed to prevent duplicate evaluations
                $task->quality_reward_issued = true;
                $task->saveQuietly();
                
                $count++;
            });
        }

        $this->info("Processed {$count} tasks for potential quality rewards.");
        return Command::SUCCESS;
    }
}
