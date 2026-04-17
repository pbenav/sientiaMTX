<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RecoverGoogleEmailsCommand extends Command
{
    protected $signature = 'google:recover-emails';
    protected $description = 'Recover missing google_email values for existing team connections';

    public function handle()
    {
        $users = \App\Models\User::whereHas('teams', function($q) {
            $q->whereNotNull('google_token')->whereNull('google_email');
        })->get();

        $googleService = app(\App\Services\GoogleService::class);
        $count = 0;

        foreach ($users as $user) {
            foreach ($user->teams as $team) {
                if ($team->pivot->google_token && !$team->pivot->google_email) {
                    $this->info("Processing User: {$user->email} for Team: {$team->name}");
                    try {
                        if ($googleService->setTokenForUser($user, $team->id)) {
                            $oauth2 = new \Google\Service\Oauth2($googleService->getClient());
                            $userInfo = $oauth2->userinfo->get();
                            
                            if ($userInfo->email) {
                                $user->teams()->updateExistingPivot($team->id, [
                                    'google_email' => $userInfo->email
                                ]);
                                $this->line("  Updated with: {$userInfo->email}");
                                $count++;
                            }
                        }
                    } catch (\Exception $e) {
                        $this->error("  Error: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("Done! Updated $count records.");
    }
}
