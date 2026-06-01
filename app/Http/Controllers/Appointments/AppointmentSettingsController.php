<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSettings;
use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentSettingsController extends Controller
{
    public function edit()
    {
        $user     = auth()->user();
        $settings = $user->appointmentSettings ?? new AppointmentSettings(['user_id' => $user->id]);
        $expedientes = Expediente::where(function($q) use ($user) {
            $q->whereHas('team', fn($tq) => $tq->whereHas('users', fn($uq) => $uq->where('user_id', $user->id)));
        })->orderBy('title')->get();

        return view('appointments.settings', compact('settings', 'expedientes'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'public_slug'           => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9\-_]+$/',
                Rule::unique('appointment_settings', 'public_slug')->ignore($user->appointmentSettings?->id),
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
        ]);

        // Limpiar slug: minúsculas sin espacios
        if (isset($data['public_slug'])) {
            $data['public_slug'] = strtolower(trim($data['public_slug']));
        }

        $user->appointmentSettings()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return back()->with('success', 'Configuración del portal de citas actualizada.');
    }
}
