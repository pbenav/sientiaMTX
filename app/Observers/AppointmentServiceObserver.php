<?php

namespace App\Observers;

use App\Models\AppointmentService;
use App\Jobs\TranslateAppointmentServiceJob;

class AppointmentServiceObserver
{
    /**
     * Handle the AppointmentService "saved" event.
     */
    public function saved(AppointmentService $service): void
    {
        // Si cambian los textos o es nuevo
        if ($service->wasChanged('name') || $service->wasChanged('description') || $service->wasRecentlyCreated) {
            // Despachamos el job
            TranslateAppointmentServiceJob::dispatch($service);
        }
    }
}
