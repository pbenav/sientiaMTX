<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Services\GoogleService;
use Illuminate\Support\Facades\Log;

class AppointmentGoogleSyncService
{
    public function __construct(protected GoogleService $googleService) {}

    public function createCalendarEvent(Appointment $appointment): void
    {
        try {
            $member = $appointment->member;
            
            if ($this->googleService->setTokenForUser($member)) {
                $description = "Cita Previa: {$appointment->service->name}\n"
                             . "Ciudadano: {$appointment->visitor->full_name}\n"
                             . "Localizador: {$appointment->localizador}";

                if (!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields)) {
                    $description .= "\n\nInformación Adicional:\n";
                    foreach ($appointment->service->custom_fields as $field) {
                        $val = $appointment->custom_fields_values[$field['id']] ?? '';
                        if (!empty($val)) {
                            $description .= "- {$field['name']}: {$val}\n";
                        }
                    }
                }

                $eventData = [
                    'summary' => '[CITA] ' . $appointment->visitor->full_name . ' - ' . $appointment->service->name,
                    'description' => $description,
                    'start' => [
                        'dateTime' => $appointment->appointment_datetime->format(\DateTime::RFC3339),
                        'timeZone' => config('app.timezone'),
                    ],
                    'end' => [
                        'dateTime' => $appointment->end_datetime->format(\DateTime::RFC3339),
                        'timeZone' => config('app.timezone'),
                    ],
                ];

                $eventId = $this->googleService->createEvent($eventData);

                if ($eventId) {
                    $appointment->update(['google_event_id' => $eventId]);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Error sincronizando cita con Google Calendar: " . $e->getMessage());
        }
    }

    public function createTask(Appointment $appointment): void
    {
        try {
            $member = $appointment->member;
            
            if ($this->googleService->setTokenForUser($member)) {
                $description = "Cita Previa: {$appointment->service->name}\n"
                             . "Ciudadano: {$appointment->visitor->full_name}\n"
                             . "Localizador: {$appointment->localizador}\n"
                             . "Día: {$appointment->appointment_date->format('d/m/Y')}\n"
                             . "Hora: {$appointment->appointment_time}";

                if (!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields)) {
                    $description .= "\n\nInformación Adicional:\n";
                    foreach ($appointment->service->custom_fields as $field) {
                        $val = $appointment->custom_fields_values[$field['id']] ?? '';
                        if (!empty($val)) {
                            $description .= "- {$field['name']}: {$val}\n";
                        }
                    }
                }

                $taskData = [
                    'title' => '[CITA] ' . $appointment->visitor->full_name . ' - ' . $appointment->service->name,
                    'notes' => $description,
                    'due'   => $appointment->appointment_datetime->format(\DateTime::RFC3339),
                ];

                $taskId = $this->googleService->createTask($taskData);

                if ($taskId) {
                    $appointment->update(['google_task_id' => $taskId]);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Error sincronizando cita con Google Tasks: " . $e->getMessage());
        }
    }
}
