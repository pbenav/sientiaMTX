<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\TaskAttachment;
use App\Http\Controllers\TaskAttachmentController;
use App\Services\Metrics\WellnessMetricsService;
use App\Services\Metrics\AppointmentMetricsService;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AttachmentDiskResolutionTest extends TestCase
{
    public function test_attachment_with_local_provider_resolves_to_public_disk()
    {
        Storage::fake('public');
        Storage::disk('public')->put('forum/attachments/test.png', 'dummy-content');

        $attachment = new TaskAttachment([
            'file_name' => 'test.png',
            'file_path' => 'forum/attachments/test.png',
            'storage_provider' => 'local',
        ]);

        $controller = new class extends TaskAttachmentController {
            public function testResolve(TaskAttachment $attachment): ?string
            {
                return $this->resolveAttachmentDisk($attachment);
            }
        };

        $resolvedDisk = $controller->testResolve($attachment);
        $this->assertEquals('public', $resolvedDisk);
    }

    public function test_attachment_with_null_provider_resolves_to_public_disk()
    {
        Storage::fake('public');
        Storage::disk('public')->put('attachments/test2.png', 'dummy-content');

        $attachment = new TaskAttachment([
            'file_name' => 'test2.png',
            'file_path' => 'attachments/test2.png',
            'storage_provider' => null,
        ]);

        $controller = new class extends TaskAttachmentController {
            public function testResolve(TaskAttachment $attachment): ?string
            {
                return $this->resolveAttachmentDisk($attachment);
            }
        };

        $resolvedDisk = $controller->testResolve($attachment);
        $this->assertEquals('public', $resolvedDisk);
    }

    public function test_metrics_services_have_required_methods()
    {
        $wellness = app(WellnessMetricsService::class);
        $this->assertTrue(method_exists($wellness, 'getBurnoutRiskByTeam'));
        $this->assertTrue(method_exists($wellness, 'getHighStressUsers'));
        $this->assertTrue(method_exists($wellness, 'getTeamOvertimeTracking'));

        $appointments = app(AppointmentMetricsService::class);
        $this->assertTrue(method_exists($appointments, 'getAppointmentStats'));
    }

    public function test_user_model_has_active_scope()
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'scopeActive'));
    }
}
