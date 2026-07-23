<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentBlock;
use App\Models\AppointmentSchedule;
use App\Models\AppointmentService;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;

/**
 * Servicio de gestión de disponibilidad para turnos de citas.
 *
 * Calcula tramos disponibles considerando horarios del miembro, bloqueos activos,
 * citas ya reservadas y duración del servicio.
 */
class AppointmentAvailabilityService
{
    /**
     * Devuelve los tramos disponibles para un servicio y fecha concretos.
     *
     * Formato: array de ['time' => '09:00', 'available' => 3, 'booked' => 1, 'full' => false].
     * Considera horarios específicos del servicio o generales del miembro, priorizando los específicos.
     * Excluye tramos pasados y bloqueos activos.
     *
     * @param  AppointmentService  $service
     * @param  Carbon  $date
     * @return array
     */
    public function getSlotsForDate(AppointmentService $service, Carbon $date): array
    {
        $user        = $service->user;
        $dayOfWeek   = $date->dayOfWeek; // 0=Dom ... 6=Sáb

        // Buscar horarios aplicables: específicos del servicio, o generales del miembro
        $schedules = AppointmentSchedule::where('user_id', $user->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($service) {
                $q->where('service_id', $service->id)->orWhereNull('service_id');
            })
            ->orderByRaw('service_id IS NULL ASC') // específico primero
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        // Si hay horarios específicos del servicio y generales a la vez, priorizamos los específicos
        $hasSpecific = $schedules->contains(fn($s) => !is_null($s->service_id));
        if ($hasSpecific) {
            $schedules = $schedules->filter(fn($s) => !is_null($s->service_id));
        }

        $slots = [];

        // Citas ya reservadas en esa fecha para ese servicio
        $booked = Appointment::where('service_id', $service->id)
            ->where('appointment_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->get()
            ->groupBy('appointment_time');

        foreach ($schedules as $schedule) {
            $slotMinutes = $schedule->slot_duration_minutes;
            $maxPerSlot  = $schedule->max_per_slot;
            $start       = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
            $end         = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

            // Bloqueos activos que cubren esta franja horaria en concreto
            $blocks = AppointmentBlock::where('user_id', $user->id)
                ->where(function ($q) use ($service) {
                    $q->where('service_id', $service->id)->orWhereNull('service_id');
                })
                ->where('start_datetime', '<=', $end)
                ->where('end_datetime', '>=', $start)
                ->get();

            $current = $start->copy();

            while ($current->copy()->addMinutes($service->duration_minutes) <= $end) {
                $timeKey      = $current->format('H:i');
                $slotEnd      = $current->copy()->addMinutes($slotMinutes);
                $bookedCount  = $booked->get($timeKey . ':00', collect())->count();

                // Comprobar si este tramo está bloqueado
                $isBlocked = $blocks->contains(function ($block) use ($current, $slotEnd) {
                    return $block->start_datetime < $slotEnd && $block->end_datetime > $current;
                });

                // Comprobar si el tramo es en el pasado
                $isPast = $current->isPast();

                if (!$isBlocked && !$isPast) {
                    // Evitar duplicados si por algún motivo coinciden franjas
                    $exists = collect($slots)->contains('time', $timeKey);
                    if (!$exists) {
                        $slots[] = [
                            'time'      => $timeKey,
                            'available' => max(0, $maxPerSlot - $bookedCount),
                            'booked'    => $bookedCount,
                            'full'      => ($bookedCount >= $maxPerSlot),
                        ];
                    }
                }

                $current->addMinutes($slotMinutes);
            }
        }

        // Ordenar tramos cronológicamente
        usort($slots, fn($a, $b) => strcmp($a['time'], $b['time']));

        return $slots;
    }

    /**
     * Devuelve los días disponibles (con al menos un tramo libre) para un mes/año.
     *
     * @param  AppointmentService  $service
     * @param  int  $year
     * @param  int  $month
     * @return array
     */
    public function getAvailableDaysInMonth(AppointmentService $service, int $year, int $month): array
    {
        $start     = Carbon::create($year, $month, 1)->startOfDay();
        $end       = $start->copy()->endOfMonth();
        $available = [];

        // No ofrecer fechas pasadas
        if ($start < now()->startOfDay()) {
            $start = now()->startOfDay();
        }

        while ($start <= $end) {
            $slots = $this->getSlotsForDate($service, $start->copy());
            $hasFree = collect($slots)->contains(fn($s) => !$s['full']);
            if ($hasFree) {
                $available[] = $start->toDateString();
            }
            $start->addDay();
        }

        return $available;
    }

    /**
     * Comprueba si un tramo concreto está disponible (para validar antes de guardar).
     *
     * @param  AppointmentService  $service
     * @param  Carbon  $date
     * @param  string  $time
     * @return bool
     */
    public function isSlotAvailable(AppointmentService $service, Carbon $date, string $time): bool
    {
        $slots = $this->getSlotsForDate($service, $date);
        $slot  = collect($slots)->firstWhere('time', $time);
        return $slot && !$slot['full'];
    }
}
