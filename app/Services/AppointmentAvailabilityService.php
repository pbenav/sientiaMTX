<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentBlock;
use App\Models\AppointmentSchedule;
use App\Models\AppointmentService;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class AppointmentAvailabilityService
{
    /**
     * Devuelve los tramos disponibles para un servicio y fecha concretos.
     * Formato: array de ['time' => '09:00', 'available' => 3, 'booked' => 1]
     */
    public function getSlotsForDate(AppointmentService $service, Carbon $date): array
    {
        $user        = $service->user;
        $dayOfWeek   = $date->dayOfWeek; // 0=Dom ... 6=Sáb

        // Buscar horario aplicable: específico del servicio, o general del miembro
        $schedule = AppointmentSchedule::where('user_id', $user->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($service) {
                $q->where('service_id', $service->id)->orWhereNull('service_id');
            })
            ->orderByRaw('service_id IS NULL ASC') // específico primero
            ->first();

        if (!$schedule) {
            return [];
        }

        $slotMinutes = $schedule->slot_duration_minutes;
        $maxPerSlot  = $schedule->max_per_slot;
        $start       = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $end         = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

        // Citas ya reservadas en esa fecha para ese servicio
        $booked = Appointment::where('service_id', $service->id)
            ->where('appointment_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled', 'blocked'])
            ->get()
            ->groupBy('appointment_time');

        // Bloqueos activos que cubren ese día
        $blocks = AppointmentBlock::where('user_id', $user->id)
            ->where(function ($q) use ($service) {
                $q->where('service_id', $service->id)->orWhereNull('service_id');
            })
            ->where('start_datetime', '<=', $end)
            ->where('end_datetime', '>=', $start)
            ->get();

        $slots = [];
        $current = $start->copy();

        while ($current->copy()->addMinutes($service->duration_minutes) <= $end) {
            $timeKey      = $current->format('H:i');
            $slotEnd      = $current->copy()->addMinutes($slotMinutes);
            $bookedCount  = $booked->get($timeKey . ':00', collect())->count();

            // Comprobar si este tramo está bloqueado
            $isBlocked = $blocks->contains(function ($block) use ($current, $slotEnd) {
                return $block->start_datetime < $slotEnd && $block->end_datetime > $current;
            });

            if (!$isBlocked) {
                $slots[] = [
                    'time'      => $timeKey,
                    'available' => max(0, $maxPerSlot - $bookedCount),
                    'booked'    => $bookedCount,
                    'full'      => ($bookedCount >= $maxPerSlot),
                ];
            }

            $current->addMinutes($slotMinutes);
        }

        return $slots;
    }

    /**
     * Devuelve los días disponibles (con al menos un tramo libre) para un mes/año.
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
     */
    public function isSlotAvailable(AppointmentService $service, Carbon $date, string $time): bool
    {
        $slots = $this->getSlotsForDate($service, $date);
        $slot  = collect($slots)->firstWhere('time', $time);
        return $slot && !$slot['full'];
    }
}
