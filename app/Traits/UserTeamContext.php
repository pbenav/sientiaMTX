<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

trait UserTeamContext
{
    /**
     * Get the user's role name in a specific team.
     */
    public function getRole(Team $team): ?string
    {
        $membership = $this->teams()->where('team_id', $team->id)->first();
        
        if (!$membership || !$membership->pivot->role_id) {
            return null;
        }

        $role = DB::table('team_roles')->where('id', $membership->pivot->role_id)->first();
        return $role ? $role->name : null;
    }

    /**
     * Determine if the user is a coordinator in the given team.
     */
    public function isCoordinator(Team $team): bool
    {
        return $team->isCoordinator($this);
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Cita Previa permitida en al menos un equipo.
     */
    public function hasAppointmentsEnabled(): bool
    {
        return $this->teams()
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->exists();
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Cita Previa permitida en un equipo específico.
     */
    public function hasAppointmentsEnabledForTeam(int $teamId): bool
    {
        return $this->teams()
            ->where('teams.id', $teamId)
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->exists();
    }

    /**
     * Obtener el primer equipo que tiene citas previas habilitadas para este usuario.
     */
    public function firstTeamWithAppointments(): ?Team
    {
        return $this->teams()
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->first();
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Micrositios permitida en al menos un equipo.
     */
    public function hasMicrositesEnabled(): bool
    {
        return $this->teams()
            ->whereJsonContains('settings->microsites_enabled', true)
            ->wherePivot('allow_microsites', true)
            ->exists();
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Micrositios permitida en un equipo específico.
     */
    public function hasMicrositesEnabledForTeam(int $teamId): bool
    {
        return $this->teams()
            ->where('teams.id', $teamId)
            ->whereJsonContains('settings->microsites_enabled', true)
            ->wherePivot('allow_microsites', true)
            ->exists();
    }

    /**
     * Obtener el primer equipo que tiene micrositios habilitados para este usuario.
     */
    public function firstTeamWithMicrosites(): ?Team
    {
        return $this->teams()
            ->whereJsonContains('settings->microsites_enabled', true)
            ->wherePivot('allow_microsites', true)
            ->first();
    }
}
