<?php

namespace App\Traits;

use App\Models\AppointmentDelegation;
use App\Models\Team;
use App\Models\User;
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
     * Comprueba si este usuario es secretario/a de un titular en un equipo específico.
     */
    public function isSecretaryOf(User|int $titular, int $teamId): bool
    {
        $titularId = $titular instanceof User ? $titular->id : (int) $titular;
        if ($this->id === $titularId) {
            return true;
        }

        return \App\Models\AppointmentDelegation::where('team_id', $teamId)
            ->where('user_id', $titularId)
            ->where('delegate_id', $this->id)
            ->exists();
    }

    /**
     * Comprobar si el usuario tiene alguna agenda delegada como secretario/a en un equipo específico.
     */
    public function hasDelegatedAppointmentsForTeam(int $teamId): bool
    {
        return \App\Models\AppointmentDelegation::where('team_id', $teamId)
            ->where('delegate_id', $this->id)
            ->exists();
    }

    /**
     * Comprobar si el usuario tiene alguna agenda delegada como secretario/a en cualquier equipo.
     */
    public function hasAnyDelegatedAppointments(): bool
    {
        return \App\Models\AppointmentDelegation::where('delegate_id', $this->id)->exists();
    }

    /**
     * Comprueba si el usuario puede acceder al módulo de citas en un equipo (propias o como secretario/a).
     */
    public function canAccessAppointmentsForTeam(int $teamId): bool
    {
        return $this->hasAppointmentsEnabledForTeam($teamId) || $this->hasDelegatedAppointmentsForTeam($teamId);
    }

    /**
     * Comprueba si el usuario puede acceder a citas en cualquier equipo (propias o como secretario/a).
     */
    public function canAccessAnyAppointments(): bool
    {
        return $this->hasAppointmentsEnabled() || $this->hasAnyDelegatedAppointments();
    }

    /**
     * Obtiene la colección de usuarios cuyas agendas de citas puede gestionar en un equipo.
     * Incluye a sí mismo (si tiene allow_appointments) y a los titulares que le han delegado su agenda.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getManageableAppointmentUsersForTeam(int $teamId): \Illuminate\Support\Collection
    {
        $users = collect();

        if ($this->hasAppointmentsEnabledForTeam($teamId)) {
            $users->push($this);
        }

        $titularIds = \App\Models\AppointmentDelegation::where('team_id', $teamId)
            ->where('delegate_id', $this->id)
            ->pluck('user_id');

        if ($titularIds->isNotEmpty()) {
            $titulares = \App\Models\User::whereIn('id', $titularIds)->orderBy('name')->get();
            $users = $users->merge($titulares);
        }

        return $users->unique('id')->values();
    }

    /**
     * Obtener el primer equipo que tiene citas previas habilitadas para este usuario (propias o delegadas).
     */
    public function firstTeamWithAppointments(): ?Team
    {
        $directTeam = $this->teams()
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->first();

        if ($directTeam) {
            return $directTeam;
        }

        $delegatedTeamId = \App\Models\AppointmentDelegation::where('delegate_id', $this->id)->value('team_id');
        if ($delegatedTeamId) {
            return Team::find($delegatedTeamId);
        }

        return null;
    }

    /**
     * Obtiene todos los equipos donde el usuario puede gestionar citas (propias o delegadas).
     *
     * @return \Illuminate\Support\Collection<int, Team>
     */
    public function teamsWithAppointments(): \Illuminate\Support\Collection
    {
        $directTeams = $this->teams()
            ->whereJsonContains('settings->has_appointments', true)
            ->wherePivot('allow_appointments', true)
            ->get();

        $delegatedTeamIds = \App\Models\AppointmentDelegation::where('delegate_id', $this->id)
            ->pluck('team_id')
            ->unique();

        if ($delegatedTeamIds->isEmpty()) {
            return $directTeams;
        }

        $delegatedTeams = Team::whereIn('id', $delegatedTeamIds)->get();

        return $directTeams->merge($delegatedTeams)->unique('id')->values();
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

    /**
     * Comprobar si el miembro tiene la funcionalidad de Encuestas permitida en al menos un equipo.
     */
    public function hasSurveysEnabled(): bool
    {
        return $this->teams()
            ->whereJsonContains('settings->surveys_enabled', true)
            ->wherePivot('allow_surveys', true)
            ->exists();
    }

    /**
     * Comprobar si el miembro tiene la funcionalidad de Encuestas permitida en un equipo específico.
     */
    public function hasSurveysEnabledForTeam(int $teamId): bool
    {
        return $this->teams()
            ->where('teams.id', $teamId)
            ->whereJsonContains('settings->surveys_enabled', true)
            ->wherePivot('allow_surveys', true)
            ->exists();
    }

    /**
     * Obtener el primer equipo que tiene encuestas habilitadas para este usuario.
     */
    public function firstTeamWithSurveys(): ?Team
    {
        return $this->teams()
            ->whereJsonContains('settings->surveys_enabled', true)
            ->wherePivot('allow_surveys', true)
            ->first();
    }
}
