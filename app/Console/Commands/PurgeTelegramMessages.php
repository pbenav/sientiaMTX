<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\TelegramMessage;

class PurgeTelegramMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:purge {team_id : The ID of the team to purge messages for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge all Telegram messages for a specific team (useful after migrating to a supergroup to avoid ID collisions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamId = $this->argument('team_id');
        $team = Team::find($teamId);

        if (!$team) {
            $this->error("Team with ID {$teamId} not found.");
            return 1;
        }

        $count = TelegramMessage::where('team_id', $team->id)->count();

        if ($count === 0) {
            $this->info("No messages found for team '{$team->name}'.");
            return 0;
        }

        if (!$this->confirm("Are you sure you want to delete all {$count} Telegram messages for team '{$team->name}'? This will also delete any associated media files.", false)) {
            $this->info("Operation cancelled.");
            return 0;
        }

        $this->info("Purging messages...");

        // We use each()->delete() to ensure model events (like file deletion and disk_used update) are triggered
        TelegramMessage::where('team_id', $team->id)->each(function ($message) {
            $message->delete();
        });

        // Final sync to be 100% sure the counters are correct
        $team->syncDiskUsed();

        $this->success("Successfully purged {$count} messages for team '{$team->name}'.");
        return 0;
    }
}
