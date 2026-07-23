<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentBlock;
use App\Models\AppointmentService;
use App\Models\Appointment;
use App\Models\Team;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión de tramos bloqueados (horarios no disponibles) en el sistema de citas.
 *
 * Permite a los usuarios crear bloques de tiempo en los que no estarán disponibles para citas,
 * y eliminarlos. Al crear un bloqueo con la opción 'notify_affected', se cancelan automáticamente
 * las citas programadas dentro de ese rango, se eliminan los eventos de Google Calendar asociados
 * y se envía un correo de cancelación a los visitantes afectados.
 *
 * Rutas asociadas:
 *   - GET /teams/{team}/appointments/blocks
 *   - POST /teams/{team}/appointments/blocks
 *   - DELETE /teams/{team}/appointments/blocks/{block}
 */
class AppointmentBlockController extends Controller
{
    /**
     * Muestra la lista de tramos bloqueados del usuario dentro de un equipo.
     *
     * Incluye los bloques generales y los asociados a servicios del equipo, junto con
     * los servicios activos del usuario en ese equipo.
     *
     * @param Team $team
     * @return \Illuminate\View\View
     */
    public function index(Team $team)
    {
        $blocks = auth()->user()->appointmentBlocks()
            ->where(function($q) use ($team) {
                $q->whereNull('service_id')
                  ->orWhereHas('service', fn($sq) => $sq->where('team_id', $team->id));
            })
            ->orderBy('start_datetime')
            ->with('service')
            ->get();

        $services = auth()->user()->appointmentServices()
            ->where('team_id', $team->id)
            ->active()
            ->get();

        return view('appointments.blocks.index', compact('blocks', 'services', 'team'));
    }

    /**
     * Crea un nuevo tramo bloqueado para el usuario autenticado.
     *
     * Verifica que el servicio asociado (si existe) pertenezca al usuario y al equipo.
     * Opcionalmente cancela las citas afectadas dentro del rango del bloqueo.
     *
     * @param Team $team
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Team $team, Request $request)
    {
        $data = $request->validate([
            'service_id'       => 'nullable|exists:appointment_services,id',
            'start_datetime'   => 'required|date|after_or_equal:now',
            'end_datetime'     => 'required|date|after:start_datetime',
            'reason'           => 'nullable|string|max:500',
            'notify_affected'  => 'boolean',
        ]);

        // Verificar que el servicio pertenece al usuario y al equipo si se especifica
        if (!empty($data['service_id'])) {
            $service = AppointmentService::find($data['service_id']);
            if ($service->user_id !== auth()->id() || $service->team_id !== $team->id) {
                abort(403);
            }
        }

        $block = auth()->user()->appointmentBlocks()->create($data);

        // Si notify_affected, enviar emails a citas afectadas
        if ($block->notify_affected) {
            $this->notifyAffectedAppointments($block, $team);
        }

        return back()->with('success', 'Tramo bloqueado correctamente.');
    }

    /**
     * Elimina un tramo bloqueado.
     *
     * Solo el propietario del bloqueo puede eliminarlo.
     *
     * @param Team $team
     * @param AppointmentBlock $block
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team, AppointmentBlock $block)
    {
        if ($block->user_id !== auth()->id()) {
            abort(403);
        }
        if ($block->service_id && $block->service->team_id !== $team->id) {
            abort(403);
        }
        
        $block->delete();
        return back()->with('success', 'Bloqueo eliminado.');
    }

    /**
     * Notifica y cancela las citas que se ven afectadas por un bloqueo de horario.
     *
     * Busca las citas del usuario dentro del rango del bloqueo, las marca como 'blocked',
     * elimina el evento de Google Calendar asociado y envía un correo de cancelación
     * al visitante si tiene consentimiento.
     *
     * @param AppointmentBlock $block
     * @param Team $team
     */
    private function notifyAffectedAppointments(AppointmentBlock $block, Team $team): void
    {
        $query = Appointment::where('user_id', auth()->id())
            ->whereHas('service', fn($q) => $q->where('team_id', $team->id))
            ->where('appointment_date', '>=', $block->start_datetime->toDateString())
            ->where('appointment_date', '<=', $block->end_datetime->toDateString())
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->with('visitor', 'service');

        if ($block->service_id) {
            $query->where('service_id', $block->service_id);
        }

        $affected = $query->get();

        foreach ($affected as $appointment) {
            // Verificar si la cita cae dentro del bloqueo
            $apptStart = $appointment->appointment_datetime;
            $apptEnd   = $appointment->end_datetime;

            if ($apptStart < $block->end_datetime && $apptEnd > $block->start_datetime) {
                $appointment->update(['status' => 'blocked']);
                $this->deleteGoogleEvent($appointment);

                if ($appointment->visitor->email && $appointment->visitor->consent_email) {
                    try {
                        \Mail::to($appointment->visitor->email)
                            ->send(new \App\Mail\AppointmentCancelledMail($appointment, $block->reason));
                    } catch (\Throwable $e) {
                        \Log::warning("Block cancel mail failed: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Elimina el evento asociado de Google Calendar para una cita.
     *
     * @param Appointment $appointment
     */
    private function deleteGoogleEvent(Appointment $appointment): void
    {
        if ($appointment->google_event_id) {
            try {
                $googleService = new \App\Services\GoogleService();
                if ($googleService->setTokenForUser($appointment->member)) {
                    $googleService->deleteEvent($appointment->google_event_id);
                    $appointment->update(['google_event_id' => null]);
                }
            } catch (\Throwable $e) {
                \Log::error("Error eliminando cita en Google Calendar: " . $e->getMessage());
            }
        }
    }
}
