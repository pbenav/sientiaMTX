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
        $days = (int) $this->option('days');
        $threshold = Carbon::now()->subDays($days);

        $activitiesCount = Activity::onlyTrashed()
            ->where('deleted_at', '<=', $threshold)
            ->forceDelete();

        $expedientesCount = Expediente::onlyTrashed()
            ->where('deleted_at', '<=', $threshold)
            ->forceDelete();

        $this->info("Successfully emptied trash. Deleted {$activitiesCount} activities and {$expedientesCount} expedientes older than {$days} days.");
    }
}
