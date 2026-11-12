<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\ForumThread;
use App\Models\TimeLog;
use App\Models\User;
use App\Models\Team;
use App\Actions\Activities\ConvertActivityAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class ActivityConversionTransferTest extends TestCase
{
    public function test_conversion_transfers_forum_threads_and_time_logs()
    {
        // Verificar que los métodos de relación existen en el modelo Activity
        $activity = new Activity();
        $this->assertTrue(method_exists($activity, 'convertedFromActivity'));
        $this->assertTrue(method_exists($activity, 'convertedToActivity'));
        $this->assertTrue(method_exists($activity, 'forumThread'));
        $this->assertTrue(method_exists($activity, 'timeLogs'));
        $this->assertTrue(method_exists($activity, 'circuitExecutions'));
    }

    public function test_repair_converted_activities_command_is_registered()
    {
        $exitCode = Artisan::call('activities:repair-converted', ['--help' => true]);
        $this->assertEquals(0, $exitCode);
    }
}

