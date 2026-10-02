<?php

namespace App\Http\Requests\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');
        $team = $this->route('team');
        
        return $appointment && $team && $appointment->service->team_id === $team->id && $this->user()->can('update', $appointment);
    }

    public function rules(): array
    {
        return [
            'appointment_date'  => 'sometimes|date',
            'appointment_time'  => 'sometimes|string',
            'status'            => 'sometimes|in:pending,confirmed,cancelled,completed,blocked,no_show',
            'member_notes'      => 'nullable|string|max:2000',
            'expediente_id'     => 'nullable|exists:expedientes,id',
            'cancellation_reason' => 'nullable|string|max:500',
            'visitor_full_name' => 'nullable|string|max:255',
            'visitor_dni'       => 'nullable|string|max:50',
            'visitor_email'     => 'nullable|email|max:255',
            'visitor_phone'     => 'nullable|string|max:50',
            'visitor_city'      => 'nullable|string|max:255',
            'visitor_postal_code' => 'nullable|string|max:50',
            'visitor_observations' => 'nullable|string|max:2000',
            'tracked_time_format' => 'nullable|string|regex:/^\d{1,3}:\d{2}:\d{2}$/',
            'custom_fields_values' => 'nullable|array',
        ];
    }
}
