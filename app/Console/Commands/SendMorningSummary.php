<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Task;
use App\Notifications\MorningSummaryNotification;
use App\Services\Ai\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendMorningSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'morning:summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a morning task summary and a motivational phrase to users who have it enabled';

    /**
     * Execute the console command.
     */
    public function handle(GeminiService $ai)
    {
        $this->info('Starting Hourly Check for Morning Summaries...');
        
        $users = User::all()->filter(function($user) {
            $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
            
            // Check if enabled
            if (empty($settings['morning_summary']) || $settings['morning_summary'] !== true) {
                return false;
            }

            // Check if it's the user's preferred time
            $preferredTime = $settings['morning_summary_time'] ?? '08:00';
            $userTz = $user->timezone ?? config('app.timezone', 'UTC');
            
            // We compare current hour in user timezone with preferred hour
            $currentHour = now($userTz)->format('H');
            $preferredHour = \Illuminate\Support\Carbon::parse($preferredTime)->format('H');
            
            return $currentHour === $preferredHour;
        });

        if ($users->isEmpty()) {
            $this->info('No users scheduled for this hour. Skipping.');
            return;
        }

        $this->info('Found ' . $users->count() . ' users to notify.');

        foreach ($users as $user) {
            // Fetch tasks for today (scheduled for today or overdue)
            $tasks = Task::where(function($q) use ($user) {
                    $q->where('assigned_user_id', $user->id)
                      ->orWhereHas('assignedTo', function($sub) use ($user) {
                          $sub->where('user_id', $user->id);
                      });
                })
                ->whereIn('status', ['pending', 'in_progress'])
                ->where(function($q) {
                    $q->whereDate('scheduled_date', '<=', now())
                      ->orWhereDate('due_date', '<=', now())
                      ->orWhereNull('scheduled_date');
                })
                ->with('team')
                ->limit(20)
                ->get();

            if ($tasks->isEmpty()) {
                $this->line("Skipping user {$user->name}: No pending tasks for today.");
                continue;
            }

            $this->line("Processing summary for {$user->name}...");

            try {
                // Generate motivational phrase using AI
                $prompt = "Genera una frase motivacional corta (máximo 15 palabras) para empezar el día. ";
                $prompt .= "El usuario tiene " . $tasks->count() . " tareas pendientes hoy. ";
                $prompt .= "Nombre del usuario: " . explode(' ', $user->name)[0] . ". ";
                $prompt .= "Idioma: " . ($user->locale ?? 'es') . ". ";
                $prompt .= "No uses hashtags ni emojis excesivos. Que sea inspiradora pero profesional.";

                $phrase = $ai->forUser($user)->generateText($prompt);
                
                // Remove any [PAYLOAD] tags if present by mistake
                $phrase = preg_replace('/\[PAYLOAD\].*?\[\/PAYLOAD\]/s', '', $phrase);
                $phrase = trim(strip_tags($phrase));

                // Send notification
                $user->notify(new MorningSummaryNotification($tasks, $phrase));
                
                $this->info("Successfully notified {$user->name}.");
            } catch (\Exception $e) {
                Log::error("Error sending morning summary to {$user->name}: " . $e->getMessage());
                $this->error("Failed to notify {$user->name}. Check logs.");
            }
        }

        $this->info('Morning Summary delivery completed.');
    }
}
