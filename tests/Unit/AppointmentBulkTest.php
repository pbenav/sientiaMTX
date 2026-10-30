<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Appointment;

class AppointmentBulkTest extends TestCase
{
    public function test_appointment_statuses_include_confirmed(): void
    {
        $this->assertArrayHasKey('confirmed', Appointment::STATUSES);
        $this->assertEquals('Confirmada', Appointment::STATUSES['confirmed']);
        $this->assertEquals('emerald', Appointment::STATUS_COLORS['confirmed']);
    }

    public function test_appointment_list_view_contains_confirmed_bulk_action_button(): void
    {
        $viewPath = resource_path('views/appointments/list.blade.php');
        $this->assertFileExists($viewPath);

        $contents = file_get_contents($viewPath);
        $this->assertStringContainsString('name="bulk_action"', $contents);
        $this->assertStringContainsString('value="confirmed"', $contents);
        $this->assertStringContainsString('bg-emerald-50', $contents);
        $this->assertStringContainsString("{{ __('Confirmado') }}", $contents);
    }
}

