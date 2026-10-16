<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentDelegation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión de secretarios/as delegados en la agenda de citas.
 *
 * Permite al titular de la agenda asignar o revocar secretarios/as dentro de un equipo.
 */
class AppointmentDelegationController extends Controller
{
    /**
     * Asigna un miembro del equipo como secretario/a de la agenda del usuario autenticado.
     */
    public function store(Team $team, Request $request)
    {
        $currentUser = auth()->user();

        // El usuario debe tener citas habilitadas para poder delegar su agenda
        if (!$currentUser->hasAppointmentsEnabledForTeam($team->id)) {
            abort(403, 'No tienes una agenda de citas activa en este equipo para delegar.');
        }

        $request->validate([
            'delegate_id' => [
                'required',
                'exists:users,id',
                'different:' . $currentUser->id,
            ],
        ], [
            'delegate_id.required' => 'Debes seleccionar un miembro del equipo.',
            'delegate_id.different' => 'No puedes asignarte a ti mismo como secretario.',
        ]);

        // Verificar que el usuario a delegar sea miembro del equipo
        $isMember = $team->members()->where('users.id', $request->delegate_id)->exists();
        if (!$isMember) {
            return redirect()->back()->withFragment('secretarias')->withErrors(['delegate_id' => 'El usuario seleccionado no pertenece a este equipo.']);
        }

        AppointmentDelegation::firstOrCreate([
            'team_id'     => $team->id,
            'user_id'     => $currentUser->id,
            'delegate_id' => $request->delegate_id,
        ]);

        $delegateUser = User::find($request->delegate_id);

        return redirect()->back()->withFragment('secretarias')->with('success', "{$delegateUser->name} ha sido asignado/a como secretario/a de tu agenda.");
    }

    /**
     * Revoca la delegación de secretaría.
     */
    public function destroy(Team $team, AppointmentDelegation $delegation)
    {
        // Solo el titular de la agenda (o el propio secretario que renuncia) puede eliminar la delegación
        $userId = auth()->id();
        if ($delegation->team_id !== $team->id || ($delegation->user_id !== $userId && $delegation->delegate_id !== $userId)) {
            abort(403, 'No tienes permiso para modificar esta delegación.');
        }

        $delegateName = $delegation->delegate->name ?? 'El usuario';
        $delegation->delete();

        return redirect()->back()->withFragment('secretarias')->with('success', "Se ha revocado la delegación de secretaría para {$delegateName}.");
    }
}
