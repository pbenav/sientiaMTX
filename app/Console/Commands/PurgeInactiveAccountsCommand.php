<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use App\Mail\InactiveUserWarningMail;
use Carbon\Carbon;

class PurgeInactiveAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-inactive-accounts {--dry-run : Simulate the process without executing real actions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans for inactive users, sends warning emails and deletes expired accounts automatically.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("--- [INACTIVE ACCOUNTS CLEANUP] Start ---");

        // 1. Check if enabled
        $isEnabled = Setting::get('purge_inactive_enabled', false, true);
        if (!$isEnabled) {
            $this->warn("Automatic purge is disabled in Global Settings. Aborting execution.");
            return Command::SUCCESS;
        }

        $inactiveDays = (int)Setting::get('purge_inactive_days', 30);
        $warningDays = (int)Setting::get('purge_warning_days', 5);
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("!!! RUNNING IN DRY-RUN MODE: NO DATABASE CHANGES OR EMAILS WILL OCCUR !!!");
        }

        $this->comment("Config: Inactive threshold = {$inactiveDays} days | Warning grace = {$warningDays} days.");

        $now = Carbon::now();
        $inactivityCutoff = $now->copy()->subDays($inactiveDays);
        $warningExpirationCutoff = $now->copy()->subDays($warningDays);

        // --- PART 1: SEND WARNING EMAILS ---
        // Users who: 
        // - Are not admins (Safety logic)
        // - Last login is before cutoff OR (Last login is null AND created_at is before cutoff)
        // - Warning NOT sent yet
        $usersToWarn = User::where('is_admin', false)
            ->where('email', '!=', 'demo@sientia.com')
            ->where(function ($query) use ($inactivityCutoff) {
                $query->where('last_activity_at', '<', $inactivityCutoff)
                      ->orWhere(function ($q) use ($inactivityCutoff) {
                          $q->whereNull('last_activity_at')->where('last_login_at', '<', $inactivityCutoff);
                      })
                      ->orWhere(function ($q) use ($inactivityCutoff) {
                          $q->whereNull('last_activity_at')->whereNull('last_login_at')->where('created_at', '<', $inactivityCutoff);
                      });
            })
            ->whereNull('inactive_warning_sent_at')
            ->get();

        $this->info("Detected " . $usersToWarn->count() . " users qualifying for NEW warning notification.");

        foreach ($usersToWarn as $user) {
            $lastSeen = $user->last_activity_at ?? $user->last_login_at ?? $user->created_at;
            $this->line("- Sending warning to: {$user->email} (Last Seen: " . ($lastSeen ? $lastSeen->toDateString() : 'N/A') . ")");
            
            if (!$dryRun) {
                try {
                    Mail::to($user->email)->send(new InactiveUserWarningMail($user, $warningDays));
                    $user->inactive_warning_sent_at = $now;
                    $user->save();
                } catch (\Exception $e) {
                    $this->error("Failed to mail user {$user->email}: " . $e->getMessage());
                }
            }
        }

        // --- PART 2: PURGE EXPIRED ACCOUNTS ---
        // Users who:
        // - Are not admins
        // - HAVE a warning timestamp
        // - Warning timestamp was longer ago than the warningDays limit
        $usersToPurge = User::where('is_admin', false)
            ->where('email', '!=', 'demo@sientia.com')
            ->whereNotNull('inactive_warning_sent_at')
            ->where('inactive_warning_sent_at', '<', $warningExpirationCutoff)
            ->get();

        $this->info("Detected " . $usersToPurge->count() . " expired users qualifying for COMPLETE PURGE.");
        foreach ($usersToPurge as $user) {
            $this->error("!!! PURGING ACCOUNT: {$user->email} (Warned on: {$user->inactive_warning_sent_at->toDateString()}) !!!");
            
            if (!$dryRun) {
                try {
                    // We execute real eloquent delete to trigger Observer cascades if any exists.
                    $user->delete(); 
                } catch (\Exception $e) {
                    $this->error("CRITICAL FAILURE purging {$user->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("--- [INACTIVE ACCOUNTS CLEANUP] Process Completed Successully ---");
        return Command::SUCCESS;
    }
}
