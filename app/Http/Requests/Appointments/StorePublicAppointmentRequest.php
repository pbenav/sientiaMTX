<?php

namespace App\Http\Requests\Appointments;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\DniNie;
use App\Models\AppointmentService;

class StorePublicAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');
        if (!$service instanceof AppointmentService) {
            return false;
        }

        $settings = $service->user->appointmentSettingsForTeam($service->team_id);
        return $settings && $settings->is_public && $service->user->hasAppointmentsEnabledForTeam($service->team_id);
    }

    public function rules(): array
    {
        $rules = [
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:150',
            'dni'           => ['required', 'string', 'max:20', new DniNie],
            'email'         => 'nullable|email:rfc,dns|max:255',
            'phone'         => ['nullable', 'string', 'max:20', 'regex:/^(\+?[0-9\s\-\.\(\)]{6,20})$/'],
            'city'          => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:10',
            'observations'  => 'nullable|string|max:2000',
            'consent_email' => 'boolean',
            'consent_data'  => 'required|accepted',
            'consent_legal' => 'required|accepted',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|string',
            'modality'         => 'required|string|in:presencial,jitsi,meet',
        ];

        $service = $this->route('service');
        if ($service && !empty($service->custom_fields)) {
            $rules['custom_fields_values'] = 'nullable|array';
            foreach ($service->custom_fields as $field) {
                $rule = $field['is_required'] ? 'required' : 'nullable';
                if ($field['type'] === 'number') {
                    $rule .= '|numeric';
                } elseif ($field['type'] === 'date') {
                    $rule .= '|date';
                } else {
                    $rule .= '|string|max:2000';
                }
                $rules['custom_fields_values.' . $field['id']] = $rule;
            }
        }

        return $rules;
    }
}
