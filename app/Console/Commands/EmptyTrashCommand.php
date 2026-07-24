<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use App\Models\Expediente;
use Carbon\Carbon;

class EmptyTrashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trash:empty {--days=30 : The number of days an item should remain in the trash before being permanently deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently deletes activities and expedientes that have been in the trash for a given number of days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $defaultDays = (int) $this->option('days');
        $teams = \App\Models\Team::all();

        $totalActivities = 0;
        $totalExpedientes = 0;

        foreach ($teams as $team) {
            // Read settings, default to global defaultDays if not set
            $settings = $team->settings ?? [];
            $days = isset($settings['trash_retention_days']) ? (int) $settings['trash_retention_days'] : $defaultDays;

            if ($days === 0) {
                // 0 means never auto-empty
                continue;
            }

            $threshold = Carbon::now()->subDays($days);

            $activitiesCount = Activity::onlyTrashed()
                ->where('team_id', $team->id)
                ->where('deleted_at', '<=', $threshold)
                ->forceDelete();

            $expedientesCount = Expediente::onlyTrashed()
                ->where('team_id', $team->id)
                ->where('deleted_at', '<=', $threshold)
                ->forceDelete();

            $totalActivities += $activitiesCount;
            $totalExpedientes += $expedientesCount;
        }

        $this->info("Successfully emptied trash. Deleted {$totalActivities} activities and {$totalExpedientes} expedientes.");
    }
}
