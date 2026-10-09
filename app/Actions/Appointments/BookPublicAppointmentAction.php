<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\AppointmentVisitor;
use App\Services\AppointmentAvailabilityService;
use App\Services\EmailValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BookPublicAppointmentAction
{
    public function __construct(
        protected AppointmentAvailabilityService $availability,
        protected EmailValidationService $emailValidation
    ) {}

    public function execute(AppointmentService $service, array $data, ?bool $overrideCapacity = false): Appointment
    {
        $date = Carbon::parse($data['appointment_date']);
        $firstName = $this->transliterateArabic($data['first_name']);
        $lastName = $this->transliterateArabic($data['last_name']);

        $checkData = array_merge($data, [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        $this->validateEmailAndDniConflicts($data, $checkData, $service);

        $lockKey = 'appointment_slot_' . $service->id . '_' . $date->format('Ymd') . '_' . str_replace(':', '', $data['appointment_time']);
        $cacheStore = config('cache.default') === 'file' ? 'database' : null;

        return Cache::store($cacheStore)->lock($lockKey, 10)->block(5, function () use ($service, $data, $date, $firstName, $lastName, $overrideCapacity) {
            return DB::transaction(function () use ($service, $data, $date, $firstName, $lastName, $overrideCapacity) {
                
                if (!$this->availability->isSlotAvailable($service, $date, $data['appointment_time'], $overrideCapacity)) {
                    throw ValidationException::withMessages(['appointment_time' => 'El tramo seleccionado ya no está disponible. Por favor, elige otro.']);
                }

                $visitor = $this->findOrCreateVisitor($data, $firstName, $lastName, $service);

                $existingAppointmentsCount = Appointment::where('visitor_id', $visitor->id)
                    ->where('appointment_date', $date->toDateString())
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->where('service_id', $service->id)
                    ->count();

                if ($existingAppointmentsCount >= 2) {
                    throw ValidationException::withMessages(['appointment_date' => 'Ya tienes el máximo de citas (2) programadas para este servicio el día seleccionado.']);
                }

                $appointment = Appointment::create([
                    'service_id'           => $service->id,
                    'user_id'              => $service->user_id,
                    'visitor_id'           => $visitor->id,
                    'status'               => 'confirmed',
                    'appointment_date'     => $date->toDateString(),
                    'appointment_time'     => $data['appointment_time'] . ':00',
                    'modality'             => $data['modality'],
                    'custom_fields_values' => $data['custom_fields_values'] ?? null,
                    'localizador'          => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
                ]);

                return $appointment;
            });
        });
    }

    protected function validateEmailAndDniConflicts(array $data, array $checkData, AppointmentService $service): void
    {
        $normalizedEmail = null;
        if (!empty($data['email'])) {
            $normalizedEmail = $this->normalizeEmail($data['email']);
            $validationResult = $this->emailValidation->verify($data['email']);
            
            if (!$validationResult['valid']) {
                throw ValidationException::withMessages(['email' => 'El correo electrónico no parece existir. Por favor, verifica que sea correcto.']);
            }
        }

        if (!empty($data['dni'])) {
            $existingDniVisitor = AppointmentVisitor::where('dni', $data['dni'])->first();
            if ($existingDniVisitor && $this->isDifferentPerson($checkData, $existingDniVisitor)) {
                throw ValidationException::withMessages(['dni' => 'Este DNI o documento ya está registrado a nombre de otra persona. Comprueba los datos introducidos.']);
            }
        }

        if (!empty($data['email'])) {
            $existingEmailVisitor = AppointmentVisitor::where(function($q) use ($data, $normalizedEmail) {
                $q->where('email', $data['email'])
                  ->orWhereRaw("CONCAT(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(email, '@', 1), '+', 1), '.', ''), '@', SUBSTRING_INDEX(email, '@', -1)) = ?", [$normalizedEmail]);
            })->first();
            
            if ($existingEmailVisitor && $this->isDifferentPerson($checkData, $existingEmailVisitor)) {
                throw ValidationException::withMessages(['email' => 'Este correo electrónico ya está registrado a nombre de otra persona. No se permite usar alias para distintas personas.']);
            }
        }

        if (!empty($data['dni']) || !empty($data['email'])) {
            $existingAppointment = Appointment::where('service_id', $service->id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->where(function($q) {
                    $q->where('appointment_date', '>', now()->toDateString())
                      ->orWhere(function($subQ) {
                          $subQ->where('appointment_date', '=', now()->toDateString())
                               ->where('appointment_time', '>=', now()->toTimeString());
                      });
                })
                ->whereHas('visitor', function ($query) use ($data, $normalizedEmail) {
                    $query->where(function ($q) use ($data, $normalizedEmail) {
                        if (!empty($data['dni'])) {
                            $q->where('dni', $data['dni']);
                        }
                        if (!empty($data['email'])) {
                            $q->orWhere(function($subQ) use ($data, $normalizedEmail) {
                                $subQ->where('email', $data['email'])
                                     ->orWhereRaw("CONCAT(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(email, '@', 1), '+', 1), '.', ''), '@', SUBSTRING_INDEX(email, '@', -1)) = ?", [$normalizedEmail]);
                            });
                        }
                    });
                })->first();

            if ($existingAppointment) {
                $formattedTime = Carbon::parse($existingAppointment->appointment_time)->format('H:i');
                $formattedDate = $existingAppointment->appointment_date;
                throw ValidationException::withMessages([
                    'appointment_date' => "Ya tienes una cita concertada para este servicio el día {$formattedDate->format('d/m/Y')} a las {$formattedTime}."
                ]);
            }
        }
    }

    protected function findOrCreateVisitor(array $data, string $firstName, string $lastName, AppointmentService $service): AppointmentVisitor
    {
        $visitor = null;
        $normalizedEmail = $this->normalizeEmail($data['email'] ?? null);
        
        if (!empty($data['dni'])) {
            $visitor = AppointmentVisitor::where('dni', $data['dni'])->lockForUpdate()->first();
        }
        
        if (!$visitor && !empty($data['email'])) {
            $visitor = AppointmentVisitor::where(function($q) use ($data, $normalizedEmail) {
                $q->where('email', $data['email'])
                  ->orWhereRaw("CONCAT(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(email, '@', 1), '+', 1), '.', ''), '@', SUBSTRING_INDEX(email, '@', -1)) = ?", [$normalizedEmail]);
            })->lockForUpdate()->first();
        }
        
        if (!$visitor) {
            $visitor = AppointmentVisitor::where('first_name', $firstName)
                         ->where('last_name', $lastName)
                         ->where('phone', $data['phone'] ?? '')
                         ->lockForUpdate()->first();
        }

        if ($visitor) {
            $visitor->update([
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'dni'           => $data['dni'] ?? $visitor->dni,
                'email'         => $data['email'] ?? $visitor->email,
                'phone'         => $data['phone'] ?? $visitor->phone,
                'city'          => $data['city'] ?? $visitor->city,
                'postal_code'   => $data['postal_code'] ?? $visitor->postal_code,
                'observations'  => $data['observations'] ?? $visitor->observations,
                'consent_email' => $data['consent_email'] ?? false,
            ]);
        } else {
            $visitor = AppointmentVisitor::create([
                'team_id'       => $service->team_id,
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
        }

        return $visitor;
    }

    protected function normalizeEmail(?string $email): ?string
    {
        if (!$email) return null;
        $email = strtolower(trim($email));
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        $local = $parts[0];
        $domain = $parts[1];
        if (in_array($domain, ['gmail.com', 'googlemail.com'])) {
            $local = explode('+', $local)[0];
            $local = str_replace('.', '', $local);
        }
        return $local . '@' . $domain;
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
