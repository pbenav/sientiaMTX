<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\GoogleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sincroniza el estado de una cita con Google Tasks.
 *
 * Este job se ejecuta en segundo plano para mantener sincronizado el estado
 * de una cita con su tarea correspondiente en Google Tasks. Verifica si la
 * tarea fue completada o eliminada en Google y actualiza el estado de la cita
 * en consecuencia. Si la tarea fue eliminada, la cita se marca como cancelada
 * con un motivo de cancelación.
 *
 * @implements ShouldQueue
 */
class SyncAppointmentWithGoogleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Crea una nueva instancia del job.
     *
     * @param Appointment $appointment La cita a sincronizar con Google.
     */
    public function __construct(public Appointment $appointment) {}

    /**
     * Ejecuta el job de sincronización con Google Tasks.
     *
     * Verifica la tarea de Google asociada a la cita y sincroniza su estado
     * con el registro local. Maneja los casos de tarea completada, tarea
     * con acción requerida y tarea eliminada.
     *
     * @param GoogleService $googleService Servicio para interactuar con la API de Google.
     * @return void
     */
    public function handle(GoogleService $googleService): void
    {
        if (!$this->appointment->google_event_id && !$this->appointment->google_task_id) {
            return;
        }

        $member = $this->appointment->member;
        if (!$member || !$googleService->setTokenForUser($member)) {
            return;
        }

        // Sync Task
        if ($this->appointment->google_task_id) {
            try {
                $task = $googleService->getTask('@default', $this->appointment->google_task_id);
                if ($task) {
                    if ($task->getStatus() === 'completed' && $this->appointment->status !== 'completed') {
                        $this->appointment->update(['status' => 'completed']);
                    } elseif ($task->getStatus() === 'needsAction' && $this->appointment->status === 'completed') {
                        $this->appointment->update(['status' => 'confirmed']);
                    }
                } else {
                    // Task was deleted
                    if (!in_array($this->appointment->status, ['cancelled', 'completed'])) {
                        $this->appointment->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Eliminada en Google Tasks']);
                    }
                }
            } catch (\Exception $e) {
                if ($e->getCode() == 404) {
                    if (!in_array($this->appointment->status, ['cancelled', 'completed'])) {
                        $this->appointment->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Eliminada en Google Tasks']);
                    }
                }
            }
        }
    }
}
