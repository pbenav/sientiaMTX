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
    protected $signature = 'morning:summary {--user= : ID of a specific user to notify (for testing)}';

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
        $targetUserId = $this->option('user');

        $users = User::all()->filter(function($user) use ($targetUserId) {
            // If a specific user is requested, only match that user and ignore time/enabled checks
            if ($targetUserId) {
                return $user->id == $targetUserId;
            }

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
            
            $match = $currentHour === $preferredHour;
            
            if (!$match) {
                Log::debug("MorningSummary: User {$user->name} skipped. Hour mismatch (User hour: {$currentHour}, Prefered hour: {$preferredHour} in {$userTz})");
            }

            return $match;
        });

        if ($users->isEmpty()) {
            if ($targetUserId) {
                $this->error("User with ID {$targetUserId} not found.");
            } else {
                $this->info('No users scheduled for this hour. Skipping.');
            }
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
                $this->line("User {$user->name}: No pending tasks for today. Sending a focus-on-rest phrase.");
            }

            $this->line("Processing summary for {$user->name}...");

            try {
                // Determine if we should attempt AI phrase generation
                $phrase = '';
                
                try {
                    // Generate motivational phrase using AI
                    $prompt = "Genera una frase motivacional corta (máximo 15 palabras) para empezar el día. ";
                    $prompt .= "El usuario tiene " . $tasks->count() . " tareas pendientes hoy. ";
                    $prompt .= "Nombre del usuario: " . explode(' ', $user->name)[0] . ". ";
                    $prompt .= "Idioma: " . ($user->locale ?? 'es') . ". ";
                    $prompt .= "No uses hashtags ni emojis excesivos. Que sea inspiradora pero profesional.";

                    $phrase = $ai->forUser($user)->generateText($prompt);
                    
                    // Check if the AI returned an error message (common if key is missing)
                    if (str_starts_with($phrase, 'Error:') || str_starts_with($phrase, 'Lo siento,')) {
                        throw new \Exception("AI unavailable: " . $phrase);
                    }

                    // Remove any [PAYLOAD] tags if present by mistake
                    $phrase = preg_replace('/\[PAYLOAD\].*?\[\/PAYLOAD\]/s', '', $phrase);
                    $phrase = trim(strip_tags($phrase));
                } catch (\Exception $aiEx) {
                    $this->warn("AI phrase generation failed for {$user->name}: " . $aiEx->getMessage());
                    
                    // Fallback phrases
                    $fallbacks = [
                        'Haz de hoy tu obra maestra.',
                        'El éxito es la suma de pequeños esfuerzos repetidos día tras día.',
                        'Hoy es una oportunidad para ser mejor que ayer.',
                        'Céntrate en el progreso, no en la perfección.',
                        'La disciplina es el puente entre las metas y el logro.',
                        'Tu esfuerzo de hoy es el éxito de mañana.',
                        'Grandes cosas nunca vinieron de zonas de confort.',
                        'Un paso a la vez, pero siempre hacia adelante.',
                        'La organización es la clave de la calma y el rendimiento.',
                        'Cree en ti mismo y en todo lo que eres capaz de lograr.'
                    ];
                    $phrase = $fallbacks[array_rand($fallbacks)];
                }

                // Reset de energía matutino (Fresh Start)
                // Si el usuario empieza el día, garantizamos al menos un 80% de energía.
                if (($user->energy_level ?? 0) < 80) {
                    $user->update(['energy_level' => 80]);
                    $this->line("Fresh Start: Energía de {$user->name} restaurada al 80%.");
                }

                // Send notification
                $user->notify(new MorningSummaryNotification($tasks, $phrase));
                
                $this->info("Successfully notified {$user->name}.");
            } catch (\Exception $e) {
                Log::error("Error sending morning summary to " . ($user->name ?? 'Unknown') . ": " . $e->getMessage());
                $this->error("Failed to notify {$user->name}: " . $e->getMessage());
            }
        }

        $this->info('Morning Summary delivery completed.');
    }
}
