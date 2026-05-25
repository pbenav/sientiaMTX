<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class PurgeGhostSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-ghost-sessions {--user= : Specific User ID to purge} {--threshold=60 : Inactivity threshold in minutes} {--force-all : Force reset ALL users not currently in an active session}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forcefully clear stale sessions and reset ghost user activity timestamps to fix presence display.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user');
        $threshold = (int)$this->option('threshold');
        $forceAll = $this->option('force-all');
        $now = Carbon::now();
        $cutoff = $now->copy()->subMinutes($threshold);

        $this->info("--- Starting Session Purge Utility ---");

        // CRITICAL INTERVENTION: If specific user is specified, destroy ALL their sessions first!
        if ($userId) {
            $this->warn("ATTENTION: Executing NUCLEAR PURGE for user ID: {$userId}");
            DB::table('sessions')->where('user_id', $userId)->delete();
            $target = User::find($userId);
            if ($target) {
                $target->remember_token = null;
                $target->last_activity_at = null;
                $target->last_login_at = null;
                $target->save();
                $this->error("TARGET TERMINATED: All sessions wiped and RememberMe revoked for {$target->name}.");
            }
            $this->info("--- Purge Complete ---");
            return Command::SUCCESS;
        }

        $this->info("Threshold: {$threshold} minutes (Cutoff: {$cutoff->toDateTimeString()})");

        // 1. Purge physical entries from database sessions table
        $deletedSessions = DB::table('sessions')
            ->where('last_activity', '<', $cutoff->getTimestamp())
            ->delete();
            
        $this->comment("Successfully cleared {$deletedSessions} expired session records from database.");

        // 2. Reset Users activity timestamps if they don't have active sessions
        $userQuery = User::query();
        if ($userId) {
            $userQuery->where('id', $userId);
        } else {
            // Only touch users that actually HAVE some activity timestamp set to save CPU, unless forced
            if (!$forceAll) {
                $userQuery->whereNotNull('last_activity_at');
            }
        }

        $usersToCheck = $userQuery->get();
        $resetCount = 0;

        $this->info("Checking status integrity for " . count($usersToCheck) . " user accounts...");

        foreach ($usersToCheck as $user) {
            // Check if user has ANY session record remaining in the sessions table
            $hasActiveSession = DB::table('sessions')
                ->where('user_id', $user->id)
                ->exists();

            // Logic: If user has NO running session, they cannot be working. 
            // We must close their active timers and reset their timestamps.
            if (!$hasActiveSession) {
                // 1. Close any active TimeLogs
                $closedLogs = $user->timeLogs()->whereNull('end_at')->update(['end_at' => Carbon::createFromTimestamp($user->last_activity_at?->timestamp ?? now()->timestamp)]);
                if ($closedLogs > 0) {
                    $this->line("<info>[TIMELOGS]</info> Closed {$closedLogs} ghost timers for User ID: {$user->id} ({$user->name}).");
                }

                // 2. User is a "ghost" if their timestamps say they are active
                if ($user->last_activity_at !== null || $user->last_login_at !== null) {
                    $user->last_activity_at = null;
                    $user->last_login_at = null;
                    // NUCLEAR FIX: Invalidate their Remember Token so their browser stops auto-logging them back in!
                    $user->remember_token = null; 
                    $user->save();
                    $resetCount++;
                    $this->line("<info>[CLEARED]</info> User ID: {$user->id} ({$user->name}) - Force set to Offline & Revoked RememberMe token.");
                }
            }
        }

        $this->info("--- Purge Complete ---");
        $this->info("Successfully forcefully logged out and reset {$resetCount} ghost users.");
        
        return Command::SUCCESS;
    }
}
