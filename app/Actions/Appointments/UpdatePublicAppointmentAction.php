<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentVisitor;
use App\Services\AppointmentAvailabilityService;
use App\Services\EmailValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class UpdatePublicAppointmentAction
{
    public function __construct(
        protected AppointmentAvailabilityService $availability,
        protected EmailValidationService $emailValidation
    ) {}

    public function execute(Appointment $appointment, array $data, ?bool $overrideCapacity = false): Appointment
    {
        $service = $appointment->service;
        $newDate = Carbon::parse($data['appointment_date']);
        $newTime = $data['appointment_time'];

        $firstName = $this->transliterateArabic($data['first_name']);
        $lastName = $this->transliterateArabic($data['last_name']);

        $checkData = array_merge($data, [
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ]);

        $this->validateEmailAndDniConflicts($data, $checkData, $appointment);

        $lockKey = 'appointment_slot_' . $service->id . '_' . $newDate->format('Ymd') . '_' . str_replace(':', '', $newTime);
        $cacheStore = config('cache.default') === 'file' ? 'database' : null;

        return Cache::store($cacheStore)->lock($lockKey, 10)->block(5, function () use ($appointment, $service, $data, $newDate, $newTime, $firstName, $lastName, $overrideCapacity) {
            return DB::transaction(function () use ($appointment, $service, $data, $newDate, $newTime, $firstName, $lastName, $overrideCapacity) {
                
                $appointment = Appointment::where('id', $appointment->id)->lockForUpdate()->first();

                $isOwnSlot = $appointment->appointment_date->eq($newDate) && $appointment->appointment_time === $newTime . ':00';

                if (!$isOwnSlot && !$this->availability->isSlotAvailable($service, $newDate, $newTime, $overrideCapacity)) {
                    throw ValidationException::withMessages(['appointment_time' => 'El tramo seleccionado ya no está disponible. Por favor, elige otro.']);
                }

                $existingOtherAppointmentsCount = Appointment::where('visitor_id', $appointment->visitor_id)
                    ->where('id', '!=', $appointment->id)
                    ->where('appointment_date', $newDate->toDateString())
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->where('service_id', $service->id)
                    ->count();

                if ($existingOtherAppointmentsCount >= 2) {
                    throw ValidationException::withMessages(['appointment_date' => 'Ya tienes el máximo de citas (2) programadas para este servicio en la nueva fecha.']);
                }

                $potentialVisitor = null;
                if (!empty($data['dni'])) {
                    $potentialVisitor = AppointmentVisitor::where('dni', $data['dni'])
                        ->where('id', '!=', $appointment->visitor_id)
                        ->lockForUpdate()
                        ->first();
                }
                if (!$potentialVisitor && !empty($data['email'])) {
                    $potentialVisitor = AppointmentVisitor::where('email', $data['email'])
                        ->where('id', '!=', $appointment->visitor_id)
                        ->lockForUpdate()
                        ->first();
                }

                if ($potentialVisitor) {
                    $appointment->visitor_id = $potentialVisitor->id;
                    $appointment->save();
                    $appointment->load('visitor');
                }

                $appointment->visitor->update([
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'dni'           => $data['dni'] ?? null,
                    'email'         => $data['email'] ?? null,
                    'phone'         => $data['phone'] ?? null,
                    'city'          => $data['city'] ?? null,
                    'postal_code'   => $data['postal_code'] ?? null,
                    'observations'  => $data['observations'] ?? null,
                    'consent_email' => $data['consent_email'] ?? false,
                ]);

                $appointment->update([
                    'appointment_date'     => $newDate->toDateString(),
                    'appointment_time'     => $newTime . ':00',
                    'modality'             => $data['modality'],
                    'custom_fields_values' => $data['custom_fields_values'] ?? null,
                ]);

                return $appointment;
            });
        });
    }

    protected function validateEmailAndDniConflicts(array $data, array $checkData, Appointment $appointment): void
    {
        if (!empty($data['email']) && $data['email'] !== $appointment->visitor->email) {
            $validationResult = $this->emailValidation->verify($data['email']);
            if (!$validationResult['valid']) {
                throw ValidationException::withMessages(['email' => 'El correo electrónico no parece existir. Por favor, verifica que sea correcto.']);
            }
        }

        if (!empty($data['dni'])) {
            $existingDniVisitor = AppointmentVisitor::where('dni', $data['dni'])
                ->where('id', '!=', $appointment->visitor_id)
                ->first();
                
            if ($existingDniVisitor && $this->isDifferentPerson($checkData, $existingDniVisitor)) {
                throw ValidationException::withMessages(['dni' => 'Este DNI o documento ya está registrado a nombre de otra persona. Comprueba los datos introducidos.']);
            }
        }

        if (!empty($data['email'])) {
            $existingEmailVisitor = AppointmentVisitor::where('email', $data['email'])
                ->where('id', '!=', $appointment->visitor_id)
                ->first();
                
            if ($existingEmailVisitor && $this->isDifferentPerson($checkData, $existingEmailVisitor)) {
                throw ValidationException::withMessages(['email' => 'Este correo electrónico ya está registrado a nombre de otra persona.']);
            }
        }
    }

    protected function transliterateArabic(?string $text): ?string
    {
        if (empty($text)) return $text;
        $arabic = ['ا','أ','إ','آ','ب','ت','ث','ج','ح','خ','د','ذ','ر','ز','س','ش','ص','ض','ط','ظ','ع','غ','ف','ق','ك','ل','م','ن','ه','و','ي','ة','ى'];
        $latin = ['a','a','e','a','b','t','th','j','h','kh','d','dh','r','z','s','sh','s','d','t','dh','a','gh','f','q','k','l','m','n','h','w','y','h','a'];
        return str_replace($arabic, $latin, $text);
    }

    protected function isDifferentPerson(array $newData, AppointmentVisitor $existing): bool
    {
        $normalize = function (?string $str) {
            if (!$str) return '';
            $str = mb_strtolower(trim($str));
            $str = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $str);
            return preg_replace('/[^a-z0-9]/', '', $str);
        };
        $newFirst = $normalize($newData['first_name'] ?? '');
        $newLast = $normalize($newData['last_name'] ?? '');
        $oldFirst = $normalize($existing->first_name);
        $oldLast = $normalize($existing->last_name);
        $distFirst = levenshtein($newFirst, $oldFirst);
        $distLast = levenshtein($newLast, $oldLast);
        $maxLenFirst = max(strlen($newFirst), strlen($oldFirst));
        $maxLenLast = max(strlen($newLast), strlen($oldLast));
        $simFirst = $maxLenFirst > 0 ? (1 - $distFirst / $maxLenFirst) : 1;
        $simLast = $maxLenLast > 0 ? (1 - $distLast / $maxLenLast) : 1;
        return ($simFirst < 0.6 || $simLast < 0.6);
    }
}
