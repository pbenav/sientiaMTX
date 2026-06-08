<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\GoogleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAppointmentWithGoogleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

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
