<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSettings;
use App\Models\Expediente;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador para la configuración del portal de citas de un usuario dentro de un equipo.
 *
 * Permite editar la configuración pública de citas: slug personalizado, nombre visible,
 * duración de turnos, capacidad máxima, integración con Google Calendar, ubicación,
 * dominio Jitsi, expedientes predeterminados y opciones de confirmación por correo.
 *
 * Rutas asociadas:
 *   - GET /teams/{team}/appointments/settings
 *   - PUT/PATCH /teams/{team}/appointments/settings
 */
class AppointmentSettingsController extends Controller
{
    /**
     * Muestra el formulario de edición de la configuración del portal de citas.
     *
     * @param Team $team
     * @return \Illuminate\View\View
     */
    public function edit(Team $team)
    {
        $user     = auth()->user();
        $settings = $user->appointmentSettings()->where('team_id', $team->id)->first()
            ?? new AppointmentSettings(['user_id' => $user->id, 'team_id' => $team->id]);
            
        $expedientes = Expediente::where('team_id', $team->id)->orderBy('title')->get();

        return view('appointments.settings', compact('settings', 'expedientes', 'team'));
    }

    /**
     * Actualiza la configuración del portal de citas del usuario para un equipo.
     *
     * Valida y persiste el slug, nombre público, duración de turnos, capacidad máxima,
     * opciones de Google Calendar, coordenadas de ubicación, dominio Jitsi y expedientes.
     * Autogenera el slug si no se proporciona uno válido.
     *
     * @param Team $team
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Team $team, Request $request)
    {
        $user = auth()->user();
        $currentSettings = $user->appointmentSettings()->where('team_id', $team->id)->first();

        $data = $request->validate([
            'public_slug'           => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9\-_]+$/',
                Rule::unique('appointment_settings', 'public_slug')->ignore($currentSettings?->id),
            ],
            'display_name'          => 'nullable|string|max:150',
            'is_public'             => 'boolean',
            'welcome_text'          => 'nullable|string',
            'legal_text'            => 'nullable|string',
            'default_slot_duration' => 'required|integer|min:5|max:120',
            'default_max_per_slot'  => 'required|integer|min:1|max:100',
            'google_calendar_enabled' => 'boolean',
            'default_expediente_id' => 'nullable|exists:expedientes,id',
            'auto_create_task'      => 'boolean',
            'email_confirmation'    => 'boolean',
            'location_lat'          => 'nullable|numeric|between:-90,90',
            'location_lng'          => 'nullable|numeric|between:-180,180',
            'jitsi_domain'          => 'nullable|string|max:100',
        ]);

        // Actualizar coordenadas en el modelo User
        $user->update([
            'location_lat' => isset($data['location_lat']) ? (float)$data['location_lat'] : null,
            'location_lng' => isset($data['location_lng']) ? (float)$data['location_lng'] : null,
        ]);

        unset($data['location_lat'], $data['location_lng']);

        // Limpiar slug o autogenerar
        if (!empty($data['public_slug'])) {
            $data['public_slug'] = strtolower(trim($data['public_slug']));
        } else {
            $data['public_slug'] = \Illuminate\Support\Str::slug($user->name) . '-' . $user->id . '-' . $team->id;
        }

        $user->appointmentSettings()->updateOrCreate(
            ['user_id' => $user->id, 'team_id' => $team->id],
            $data
        );

        return back()->with('success', 'Configuración del portal de citas actualizada.');
    }
}
