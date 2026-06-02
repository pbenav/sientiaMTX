<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentBlock;
use App\Models\AppointmentService;
use App\Models\Appointment;
use App\Models\Team;
use Illuminate\Http\Request;

class AppointmentBlockController extends Controller
{
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
