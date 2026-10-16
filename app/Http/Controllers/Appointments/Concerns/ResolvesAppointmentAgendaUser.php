<?php

namespace App\Http\Controllers\Appointments\Concerns;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesAppointmentAgendaUser
{
    /**
     * Resuelve el usuario titular de la agenda activa para este equipo.
     *
     * Si se pasa ?agenda_user_id=X y el usuario autenticado tiene permiso de gestión
     * sobre X, se fija en sesión. En caso contrario, se toma de sesión o por defecto
     * el propio usuario (si tiene citas habilitadas) o el primer titular delegado.
     *
     * @param  Team  $team
     * @param  Request|null  $request
     * @return User
     */
    protected function resolveAgendaUser(Team $team, ?Request $request = null): User
    {
        $request = $request ?? request();
        $currentUser = auth()->user();
        $manageableUsers = $currentUser->getManageableAppointmentUsersForTeam($team->id);

        if ($manageableUsers->isEmpty()) {
            abort(403, 'No tienes permisos para gestionar citas en este equipo.');
        }

        // 1. Parámetro explícito en la petición
        if ($request->filled('agenda_user_id')) {
            $requestedId = (int) $request->input('agenda_user_id');
            $found = $manageableUsers->firstWhere('id', $requestedId);
            if ($found) {
                session(["appointments_agenda_{$team->id}" => $requestedId]);
                return $found;
            }
        }

        // 2. Comprobar sesión existente
        $sessionUserId = session("appointments_agenda_{$team->id}");
        if ($sessionUserId) {
            $found = $manageableUsers->firstWhere('id', (int) $sessionUserId);
            if ($found) {
                return $found;
            }
        }

        // 3. Por defecto: el propio usuario si tiene citas habilitadas, sino el primer titular delegado
        $default = $manageableUsers->firstWhere('id', $currentUser->id) ?? $manageableUsers->first();
        session(["appointments_agenda_{$team->id}" => $default->id]);

        return $default;
    }
}
