<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

use App\Traits\AwardsGamification;

/**
 * Controlador de integración con Google (Calendar, Tasks, Meet).
 *
 * Maneja:
 *   - Autenticación OAuth2 con Google
 *   - Sincronización bidireccional de tareas con Google Tasks
 *   - Exportación de tareas a Google Calendar
 *   - Importación de eventos y tareas desde Google
 *   - Desconexión de cuentas y tareas individuales
 */
class GoogleController extends Controller
{
    use AwardsGamification;

    /**
     * Servicio de integración con Google.
     *
     * @var GoogleService
     */
    protected $googleService;

    /**
     * Inyecta el servicio de Google.
     */
    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
    }

    /**
     * Redirige al usuario a la página de autenticación de Google OAuth2.
     *
     * Captura el team_id y el modo popup en el estado para restaurar el contexto
     * en el callback.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request)
    {
        if (!$this->googleService->isConfigured()) {
            return redirect()->route('dashboard')->with('error', __('google.not_configured'));
        }

        $state = [];
        if ($request->has('popup')) {
            $state['popup'] = 1;
            session(['google_auth_is_popup' => true]);
        }
        if ($request->has('team_id')) $state['team_id'] = $request->team_id;

        if (!empty($state)) {
            $this->googleService->getClient()->setState(json_encode($state));
        }

        return redirect()->away($this->googleService->getClient()->createAuthUrl());
    }

    /**
     * Maneja el callback OAuth2 de Google.
     *
     * Intercambia el código por tokens, actualiza el pivot team-user con
     * google_id, google_email, google_token y google_refresh_token.
     * Detecta modo popup para cerrar la ventana o redirigir al perfil.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('dashboard')->with('error', __('google.auth_failed', ['error' => $request->error]));
        }

        if (!$request->has('code')) {
            return redirect()->route('dashboard')->with('error', __('google.invalid_callback'));
        }

        try {
            $token = $this->googleService->getClient()->fetchAccessTokenWithAuthCode($request->code);
            Log::info("Google Callback: Token fetched success", ['has_refresh' => isset($token['refresh_token'])]);

            if (isset($token['error'])) {
                Log::error("Google Callback: Token error", ['error' => $token['error']]);
                return redirect()->route('dashboard')->with('error', __('google.token_error', ['error' => $token['error']]));
            }

            $user = Auth::user();
            if (!$user) {
                Log::error("Google Callback: NO USER LOGGED IN");
                return redirect()->route('login')->with('error', 'Sesión expirada');
            }

            $stateData = json_decode($request->state, true) ?? [];
            $teamId = $stateData['team_id'] ?? session('google_auth_team_id');
            Log::info("Google Callback: Context Info", ['user' => $user->id, 'team' => $teamId]);

            // Reliable popup detection via session + state fallback
            $isPopup = session()->pull('google_auth_is_popup', false) || ($stateData['popup'] ?? false);

            // Fetch Google ID for reference
            $oauth2 = new \Google\Service\Oauth2($this->googleService->getClient());
            $userInfo = $oauth2->userinfo->get();
            Log::info("Google Callback: User Info", ['google_id' => $userInfo->id]);

            $refreshToken = $token['refresh_token'] ?? null;

            if ($teamId) {
                // Recover refresh token if missing
                if (!$refreshToken) {
                    $existing = $user->teams()->find($teamId);
                    $refreshToken = $existing ? $existing->pivot->google_refresh_token : null;
                    Log::info("Google Callback: Recovered Refresh Token from Pivot", ['success' => !!$refreshToken]);
                }

                $token['created'] = time(); // Store initial creation time

                $user->teams()->updateExistingPivot($teamId, [
                    'google_id' => $userInfo->id,
                    'google_email' => $userInfo->email,
                    'google_token' => $token, 
                    'google_refresh_token' => $refreshToken,
                ]);
                Log::info("Google Callback: UPDATED PIVOT for team: $teamId");
            }

            // Redundant save and refresh removed (Fix #9)

            if ($isPopup) {
                Log::info("Google Callback: Closing Popup Window");
                return view('google.callback-success');
            }

            return Redirect::route('profile.edit', [
                'tab' => 'integrations',
                'team_id' => $teamId
            ])->with('status', 'google-connected');
        } catch (\Exception $e) {
            Log::error('Google callback exception: ' . $e->getMessage());
            
            if ($isPopup) {
                $errorMsg = addslashes($e->getMessage());
                return "<html><body><script>alert(\"Authentication failed: {$errorMsg}\"); window.close();</script></body></html>";
            }

            return redirect()->route('dashboard')->with('error', __('google.auth_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Muestra eventos de Google Calendar y tareas para que el usuario seleccione qué importar.
     *
     * Carga hasta 50 eventos de calendario y 50 tareas, verificando cuáles ya existen
     * localmente. Combina ambos tipos y los ordena por fecha.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function sync(Request $request)
    {
        $user = Auth::user();
        
        $teamId = $request->input('team_id');
        if (!$teamId) {
            return back()->with('error', __('google.team_id_required'));
        }

        if (!$this->googleService->setTokenForUser($user, $teamId)) {
            return redirect()->route('google.auth', ['team_id' => $teamId])->with('info', __('google.connect_account_first'));
        }

        $team = \App\Models\Team::findOrFail($teamId);
        
        // Fetch Calendar Events
        $events = $this->googleService->listEvents(50);
        $eventsData = collect($events)->map(function($event) use ($teamId, $user) {
            $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
            $end = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();
            $title = $event->getSummary() ?: 'Evento sin título';
            $location = $event->getLocation();
            
            // Extraer enlace de Google Meet (hangoutLink o conferenceData)
            $hangoutLink = $event->getHangoutLink();
            if (!$hangoutLink && $event->getConferenceData()) {
                $entryPoints = $event->getConferenceData()->getEntryPoints();
                if (!empty($entryPoints)) {
                    foreach ($entryPoints as $ep) {
                        if ($ep->getEntryPointType() === 'video' || $ep->getUri()) {
                            $hangoutLink = $ep->getUri();
                            break;
                        }
                    }
                }
            }

            // Attendees count
            $attendeesCount = count($event->getAttendees() ?: []);

            // Calculate duration in minutes if datetime is present
            $durationMinutes = 60;
            if ($event->getStart()->getDateTime() && $event->getEnd()->getDateTime()) {
                $startTs = strtotime($event->getStart()->getDateTime());
                $endTs = strtotime($event->getEnd()->getDateTime());
                $durationMinutes = max(1, round(($endTs - $startTs) / 60));
            }

            // Check existence in Activity table (direct or via google_calendar_event_id)
            $exists = \App\Models\Activity::where('team_id', $teamId)
                ->where(function($q) use ($event, $title, $start) {
                    $q->where('google_calendar_event_id', $event->id)
                      ->orWhere(function($sub) use ($title, $start) {
                          $sub->where('title', $title)
                              ->where('scheduled_date', date('Y-m-d H:i:s', strtotime($start)));
                      });
                })
                ->exists();

            return [
                'id'                    => 'cal:' . $event->id,
                'title'                 => $title,
                'description'           => $event->getDescription() ?: '',
                'start'                 => $start,
                'end'                   => $end,
                'location'              => $location,
                'hangout_link'          => $hangoutLink,
                'attendees_count'       => $attendeesCount,
                'duration_minutes'      => $durationMinutes,
                'exists'                => $exists,
                'type'                  => 'calendar',
                'default_activity_type' => 'meeting',
            ];
        });

        // Fetch Google Tasks
        $tasks = $this->googleService->listTasks(50);
        $tasksData = collect($tasks)->map(function($task) use ($teamId, $user) {
            $due = $task->getDue() ?: now()->toIso8601String();
            $title = $task->getTitle() ?: 'Tarea sin título';
            
            // Remove Google Space/Doc context brackets e.g. "[Space Name] Task Title" -> "Task Title"
            $title = preg_replace('/^\[.*?\]\s*/', '', $title);
            
            $googleId = 'task:' . $task->id;
            $rawId = $task->id;
            
            // Robust matching: prioritized by google_task_id, then by title+date
            $exists = \App\Models\Activity::where('team_id', $teamId)
                ->where(function($q) use ($googleId, $rawId, $title, $due) {
                    $q->where('google_task_id', $googleId)
                      ->orWhere('google_task_id', $rawId)
                      ->orWhere(function($sub) use ($title, $due) {
                          $sub->where('title', 'LIKE', $title . '%')
                              ->whereDate('scheduled_date', date('Y-m-d', strtotime($due)));
                      });
                })
                ->exists();

            return [
                'id'                    => $googleId,
                'title'                 => $title,
                'description'           => ($task->getNotes() ?: '') . ($task->listTitle ? " [" . $task->listTitle . "]" : ""),
                'start'                 => $due,
                'end'                   => $due,
                'location'              => null,
                'hangout_link'          => null,
                'duration_minutes'      => null,
                'exists'                => $exists,
                'type'                  => 'task',
                'default_activity_type' => 'task',
            ];
        });

        // Combine and sort by date
        $combined = $eventsData->concat($tasksData)->sortBy('start');

        $teamUser = $user->teams()->where('team_id', $teamId)->first();
        $googleEmail = $teamUser ? $teamUser->pivot->google_email : null;

        return view('google.select-tasks', [
            'events' => $combined,
            'team' => $team,
            'visibility' => $request->input('visibility', 'private'),
            'googleEmail' => $googleEmail
        ]);
    }

    /**
     * Importa eventos de calendario y tareas seleccionadas desde Google creando Actividades directamente.
     *
     * Permite especificar el tipo de actividad para cada ítem importado (meeting, task, reminder, note, document).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request, \App\Actions\Google\ImportGoogleDataAction $importAction)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $teamId = $request->input('team_id');
        $selectedEventIds = $request->input('events', []);
        $activityTypes = $request->input('types', []);

        if (empty($selectedEventIds)) {
            return back()->with('error', __('google.no_tasks_selected'));
        }

        if (!$this->googleService->setTokenForUser($user, $teamId)) {
            return redirect()->route('google.auth', ['team_id' => $teamId])->with('info', __('google.connect_account_first'));
        }

        $syncCount = $importAction->execute($user, $teamId, $selectedEventIds, $activityTypes);

        return redirect()->route('teams.activities.index', $teamId)
            ->with('success', __('google.import_success', ['count' => $syncCount]));
    }

    /**
     * Desconecta la cuenta Google del equipo o globalmente (limpia tokens).
     *
     * Si se proporciona team_id, limpia solo los pivotes de ese equipo.
     * Si no, limpia los campos Google del usuario globalmente (legacy).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect(Request $request)
    {
        $user = auth()->user();
        $teamId = $request->query('team_id') ?? $request->input('team_id');

        if ($teamId) {
            $user->teams()->updateExistingPivot($teamId, [
                'google_id' => null,
                'google_email' => null,
                'google_token' => null,
                'google_refresh_token' => null,
            ]);
            return Redirect::route('profile.edit', [
                'tab' => 'integrations',
                'team_id' => $teamId
            ])->with('status', 'google-team-disconnected');
        }

        // Legacy/Global disconnect
        $user->google_id = null;
        $user->google_email = null;
        $user->google_token = null;
        $user->google_refresh_token = null;
        $user->save();

        return Redirect::route('profile.edit', ['tab' => 'integrations'])->with('status', 'google-disconnected');
    }

    /**
     * Desconecta una actividad de Google Tasks/Calendar localmente.
     *
     * Intenta eliminar el recurso en Google antes de limpiar los IDs locales.
     *
     * @param  \App\Models\Team  $team
     * @param  int  $taskId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnectTask(\App\Models\Team $team, $taskId, Request $request, \App\Actions\Google\DisconnectGoogleTaskAction $disconnectAction)
    {
        $task = \App\Models\Activity::find($taskId) ?? \App\Models\Task::find($taskId);
        if (!$task || $task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->cannot('update', $task)) {
            return redirect()->back()->with('warning', __('tasks.unauthorized_edit'));
        }

        if (!$this->googleService->setTokenForUser($user, $team->id)) {
            return redirect()->route('google.auth', ['team_id' => $team->id])->with('info', __('google.connect_account_first'));
        }

        $deleteInGoogle = $request->boolean('delete_in_google');
        $result = $disconnectAction->execute($user, $team, $task, $deleteInGoogle);

        $type = $result['success'] ? 'success' : 'error';
        return redirect()->back()->with($type, $result['message']);
    }


    /**
     * Sincroniza una tarea específica con Google Tasks de forma bidireccional.
     *
     * Si la tarea no tiene google_task_id, la exporta. Si ya está exportada, compara
     * timestamps de Google vs local vs última sincronización para determinar qué lado
     * es más reciente y propagar los cambios. Maneja propagación de títulos en plantillas
     * e instancias, y sincronización ascendente de progreso en jerarquía padre.
     *
     * @param  \App\Models\Team  $team
     * @param  int  $taskId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncTask(\App\Models\Team $team, $taskId, \App\Actions\Google\SyncTaskWithGoogleAction $syncAction)
    {
        $task = \App\Models\Activity::find($taskId) ?? \App\Models\Task::find($taskId);
        if (!$task || $task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->cannot('view', $task)) {
            return redirect()->back()->with('warning', __('tasks.unauthorized_view'));
        }

        if (!$this->googleService->setTokenForUser($user, $team->id)) {
            return redirect()->route('google.auth', ['team_id' => $team->id])->with('info', __('google.connect_account_first'));
        }

        $result = $syncAction->execute($user, $team, $task);
        
        $type = $result['success'] ? 'success' : 'error';
        return redirect()->back()->with($type, $result['message']);
    }

    /**
     * Exporta una tarea a Google Calendar como evento, o la quita si ya existe.
     *
     * Modo toggle: si ya tiene google_calendar_event_id, lo elimina; si no, lo crea.
     * Incluye asistentes (asignados internos + invitados externos) y envía invitaciones
     * nativas de Google Calendar.
     *
     * @param  \App\Models\Team  $team
     * @param  int  $taskId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportTaskToCalendar(\App\Models\Team $team, $taskId, \App\Actions\Google\ExportTaskToCalendarAction $exportAction)
    {
        $task = \App\Models\Activity::find($taskId) ?? \App\Models\Task::find($taskId);
        if (!$task || $task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        $user = auth()->user();
        if ($user->cannot('view', $task)) {
            return redirect()->back()->with('warning', __('tasks.unauthorized_view'));
        }

        if (!$this->googleService->setTokenForUser($user, $team->id)) {
            return redirect()->route('google.auth', ['team_id' => $team->id])->with('info', __('google.connect_account_first'));
        }

        $result = $exportAction->execute($user, $team, $task);
        
        $type = $result['success'] ? 'success' : 'error';
        return redirect()->back()->with($type, $result['message']);
    }
}
