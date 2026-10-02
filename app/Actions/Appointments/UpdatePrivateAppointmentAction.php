<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Services\Appointments\AppointmentGoogleSyncService;
use App\Services\Appointments\AppointmentAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UpdatePrivateAppointmentAction
{
    public function __construct(
        protected AppointmentGoogleSyncService $googleSyncService,
        protected AppointmentAvailabilityService $availability
    ) {}

    public function execute(Appointment $appointment, array $data): array
    {
        // 1. Availability validation (if date/time changed)
        if (isset($data['appointment_date']) || isset($data['appointment_time'])) {
            $newDate = Carbon::parse($data['appointment_date'] ?? $appointment->appointment_date);
            $newTime = $data['appointment_time'] ?? $appointment->appointment_time;

            $isOwnSlot = $appointment->appointment_date->eq($newDate) && $appointment->appointment_time === $newTime . ':00';

            if (!$isOwnSlot && !$this->availability->isSlotAvailable($appointment->service, $newDate, $newTime)) {
                return ['success' => false, 'error_field' => 'appointment_time', 'message' => 'El tramo seleccionado no está disponible.'];
            }
        }

        $originalDate = $appointment->appointment_date;
        $originalTime = $appointment->appointment_time;

        // 2. Cancellation and Google Cleanup
        if (isset($data['status']) && in_array($data['status'], ['cancelled', 'blocked'])) {
            $data['cancelled_at'] = now();
            // Delete associated Google events/tasks
            if ($appointment->activity) {
                if ($appointment->activity->google_calendar_event_id) {
                    $this->googleSyncService->deleteEvent($appointment->activity->google_calendar_event_id);
                }
                if ($appointment->activity->google_task_id) {
                    $this->googleSyncService->deleteTask('@default', $appointment->activity->google_task_id);
                }
            } elseif ($appointment->task) {
                if ($appointment->task->google_calendar_event_id) {
                    $this->googleSyncService->deleteEvent($appointment->task->google_calendar_event_id);
                }
                if ($appointment->task->google_task_id) {
                    $this->googleSyncService->deleteTask('@default', $appointment->task->google_task_id);
                }
            }
        }

        // 3. Update appointment fields
        $appointment->update($data);

        // 4. Update Visitor
        $visitorData = [];
        if (array_key_exists('visitor_full_name', $data)) $visitorData['full_name'] = $data['visitor_full_name'];
        if (array_key_exists('visitor_dni', $data)) $visitorData['dni'] = $data['visitor_dni'] ? strtoupper(trim($data['visitor_dni'])) : null;
        if (array_key_exists('visitor_email', $data)) $visitorData['email'] = $data['visitor_email'] ? strtolower(trim($data['visitor_email'])) : null;
        if (array_key_exists('visitor_phone', $data)) $visitorData['phone'] = $data['visitor_phone'];
        if (array_key_exists('visitor_city', $data)) $visitorData['city'] = $data['visitor_city'];
        if (array_key_exists('visitor_postal_code', $data)) $visitorData['postal_code'] = $data['visitor_postal_code'];
        if (array_key_exists('visitor_observations', $data)) $visitorData['observations'] = $data['visitor_observations'];

        if (!empty($visitorData)) {
            $appointment->visitor->update($visitorData);
        }

        // 5. Update custom fields
        if (isset($data['custom_fields_values']) && is_array($data['custom_fields_values'])) {
            $currentValues = $appointment->custom_fields_values ?? [];
            $newValues = array_merge($currentValues, $data['custom_fields_values']);
            $appointment->update(['custom_fields_values' => $newValues]);
        }

        // 6. Notify visitor if date/time changed
        $dateChanged = isset($data['appointment_date']) && Carbon::parse($data['appointment_date'])->ne($originalDate);
        $timeChanged = isset($data['appointment_time']) && $data['appointment_time'] . ':00' !== $originalTime;

        if (($dateChanged || $timeChanged) && $appointment->visitor->consent_email && $appointment->visitor->email) {
            try {
                Mail::to($appointment->visitor->email)
                    ->locale(app()->getLocale())
                    ->send(new \App\Mail\AppointmentModifiedMail($appointment));
            } catch (\Throwable $e) {
                Log::warning("AppointmentModified mail failed: " . $e->getMessage());
            }
        }

        // 7. Sync Activity / Task status and times
        $task = $appointment->task;
        if (!$task && !$appointment->activity && $appointment->localizador) {
            $task = \App\Models\Task::where('title', 'like', "% — {$appointment->localizador}")->first();
            if ($task) {
                $appointment->update(['task_id' => $task->id]);
            }
        }

        $this->syncTaskData($task, $appointment, $data);
        $this->syncActivityData($appointment->activity, $appointment, $data);

        // 8. Tracked time
        if (isset($data['tracked_time_format']) && ($appointment->activity || $appointment->task)) {
            $taskObj = $appointment->activity ?? $appointment->task;
            $timeFormat = $data['tracked_time_format'];
            
            $taskObj->timeEntries()->where('description', 'Cita ' . $appointment->localizador)->delete();
            
            if ($timeFormat !== '0:00:00') {
                $parts = explode(':', $timeFormat);
                $seconds = 0;
                if (count($parts) === 3) {
                    $seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
                }
                if ($seconds > 0) {
                    $taskObj->timeEntries()->create([
                        'user_id' => auth()->id() ?? $appointment->service->team->users()->first()->id,
                        'description' => 'Cita ' . $appointment->localizador,
                        'duration_seconds' => $seconds,
                    ]);
                }
            }
            $taskObj->updateTrackedTime();
        }

        return ['success' => true];
    }

    protected function syncTaskData($task, Appointment $appointment, array $data)
    {
        if (!$task) return;

        $taskData = [];
        if (isset($data['appointment_date']) || isset($data['appointment_time'])) {
            $taskData['due_date'] = $appointment->end_datetime;
        }
        if (array_key_exists('expediente_id', $data)) {
            $taskData['expediente_id'] = $data['expediente_id'];
        }
        if (isset($data['status'])) {
            if ($data['status'] === 'completed') {
                $taskData['status'] = 'completed';
                $taskData['progress_percentage'] = 100;
            } elseif (in_array($data['status'], ['pending', 'confirmed'])) {
                if ($task->status === 'completed') {
                    $taskData['status'] = 'in_progress';
                    $taskData['progress_percentage'] = 0;
                }
            }
        }
        if (!empty($taskData)) {
            $task->update($taskData);
        }
    }

    protected function syncActivityData($activity, Appointment $appointment, array $data)
    {
        if (!$activity) return;

        $activityData = [];
        if (isset($data['appointment_date']) || isset($data['appointment_time'])) {
            $activityData['due_date'] = $appointment->end_datetime;
            $activityData['scheduled_date'] = $appointment->appointment_datetime;
        }
        if (array_key_exists('expediente_id', $data)) {
            $activityData['expediente_id'] = $data['expediente_id'];
        }
        if (isset($data['status'])) {
            if ($data['status'] === 'completed') {
                $activityData['status'] = ['value' => 'completed'];
                $activityData['progress_percentage'] = 100;
            } elseif (in_array($data['status'], ['pending', 'confirmed'])) {
                if (($activity->status['value'] ?? '') === 'completed') {
                    $activityData['status'] = ['value' => 'scheduled'];
                    $activityData['progress_percentage'] = 0;
                }
            }
        }
        if (!empty($activityData)) {
            $activity->update($activityData);
        }
    }
}
