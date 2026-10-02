<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentSettings;
use App\Models\Activity;
use App\Models\AppointmentService;
use App\Mail\AppointmentConfirmedMail;
use App\Mail\AppointmentNewRequestMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SyncAppointmentWithGoogleJob;

class AppointmentPostBookingService
{
    public function __construct(protected AppointmentGoogleSyncService $googleSync) {}

    /**
     * Process all post-booking actions (Tasks, Google, Emails).
     */
    public function handleNewAppointment(Appointment $appointment): void
    {
        $service = $appointment->service;
        $settings = $service->user->appointmentSettingsForTeam($service->team_id);
        
        if (!$settings) {
            return;
        }

        if ($settings->auto_create_task) {
            $this->createActivityForAppointment($appointment, $settings);
        }

        if ($service->sync_to_google_calendar && $service->user->google_token) {
            $this->googleSync->createCalendarEvent($appointment);
        }

        if ($service->sync_to_google_tasks && $service->user->google_token) {
            $this->googleSync->createTask($appointment);
        }

        $this->sendNewAppointmentEmails($appointment, $settings);
    }

    /**
     * Process post-update actions.
     */
    public function handleUpdatedAppointment(Appointment $appointment): void
    {
        if ($appointment->activity) {
            $appointment->activity->update([
                'title'          => '[CITA] ' . $appointment->service->name . ' — ' . $appointment->localizador,
                'due_date'       => $appointment->end_datetime,
                'scheduled_date' => $appointment->appointment_datetime,
            ]);
        }

        if ($appointment->google_event_id || $appointment->google_task_id) {
            SyncAppointmentWithGoogleJob::dispatch($appointment);
        }
    }

    protected function createActivityForAppointment(Appointment $appointment, AppointmentSettings $settings): void
    {
        try {
            $member = $appointment->member;
            $expedienteId = $settings->default_expediente_id;

            $description = "**Visitante:** {$appointment->visitor->full_name}\n"
                . "**Localizador:** {$appointment->localizador}\n"
                . "**Servicio:** {$appointment->service->name}\n"
                . "**Modalidad:** " . (AppointmentService::MODALITIES[$appointment->modality] ?? $appointment->modality) . "\n"
                . "**Fecha:** {$appointment->appointment_date->format('d/m/Y')} a las {$appointment->appointment_time}";

            if (!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields)) {
                $description .= "\n\n**Información Adicional:**\n";
                foreach ($appointment->service->custom_fields as $field) {
                    $val = $appointment->custom_fields_values[$field['id']] ?? '';
                    if (!empty($val)) {
                        $description .= "- **{$field['name']}:** {$val}\n";
                    }
                }
            }

            if (in_array($appointment->modality, ['jitsi', 'meet'])) {
                $videoUrl = route('public.appointments.video.auth', $appointment) . '?localizador=' . $appointment->localizador;
                $description .= "\n\n💻 **Videoconferencia:** [Iniciar Videoconferencia]({$videoUrl}) (Modalidad: " . ucfirst($appointment->modality) . ")";
            }

            $activity = new Activity([
                'uuid'           => Str::uuid()->toString(),
                'title'          => '[CITA] ' . $appointment->service->name . ' — ' . $appointment->localizador,
                'description'    => $description,
                'status'         => ['value' => 'scheduled'],
                'priority'       => 'medium',
                'type'           => 'meeting',
                'due_date'       => $appointment->end_datetime,
                'scheduled_date' => $appointment->appointment_datetime,
                'original_due_date' => clone $appointment->end_datetime,
                'created_by_id'  => $member->id,
                'expediente_id'  => $expedienteId,
                'metadata'       => [
                    'is_ephemeral' => true,
                    'location'     => AppointmentService::MODALITIES[$appointment->modality] ?? $appointment->modality,
                ],
            ]);
            
            $activity->team_id = $appointment->service->team_id ?? clone $member->favorite_team_id;
            $activity->save();

            $activity->assignments()->create([
                'user_id' => $member->id,
                'assigned_by_id' => $member->id,
                'assigned_at' => now(),
            ]);

            $appointment->update([
                'activity_id'   => $activity->id,
                'expediente_id' => $expedienteId,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error creando tarea para cita {$appointment->localizador}: " . $e->getMessage());
        }
    }

    protected function sendNewAppointmentEmails(Appointment $appointment, AppointmentSettings $settings): void
    {
        $visitor = $appointment->visitor;
        $service = $appointment->service;

        if ($visitor->consent_email && $visitor->email && $settings->email_confirmation) {
            try {
                Mail::to($visitor->email)
                    ->locale(app()->getLocale())
                    ->send(new AppointmentConfirmedMail($appointment));
            } catch (\Throwable $e) {
                Log::warning("AppointmentConfirmed mail failed: " . $e->getMessage());
            }
        }

        try {
            Mail::to($service->user->email)
                ->locale($service->user->preferredLocale())
                ->send(new AppointmentNewRequestMail($appointment));
        } catch (\Throwable $e) {
            Log::warning("AppointmentNewRequest mail failed: " . $e->getMessage());
        }
    }
}
