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
        // Si cambian los textos, campos personalizados o es nuevo
        if ($service->wasChanged('name') || $service->wasChanged('description') || $service->wasChanged('custom_fields') || $service->wasRecentlyCreated) {
            // Despachamos el job
            TranslateAppointmentServiceJob::dispatch($service);
        }
    }
}
