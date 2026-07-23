<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Recupera y actualiza los valores de google_email faltantes para conexiones de equipo existentes.
 *
 * Identifica usuarios vinculados a equipos con token de Google pero sin google_email
 * almacenado, consulta la API de Google para obtener el correo electrónico asociado
 * y actualiza el pivot de la relación equipo-usuario.
 *
 * # Ejecución
 * ```bash
 * php artisan google:recover-emails
 * ```
 *
 * @author  SientiaMTX Team
 * @version 1.0.0
 */
class RecoverGoogleEmailsCommand extends Command
{
    /**
     * Firma del comando.
     */
    protected $signature = 'google:recover-emails';

    /**
     * Descripción del comando.
     */
    protected $description = 'Recover missing google_email values for existing team connections';

    /**
     * Punto de entrada principal del comando.
     *
     * Recorre los usuarios con equipos que posean google_token pero carezcan de
     * google_email, utiliza GoogleService para autenticar y obtiene el email vía
     * la API de OAuth2 de Google. Actualiza el pivot y reporta el total de registros
     * modificados.
     *
     * @return void
     */
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
