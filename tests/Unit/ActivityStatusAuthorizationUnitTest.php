<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\Team;
use App\Models\User;
use App\Http\Controllers\ActivityController;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ActivityStatusAuthorizationUnitTest extends TestCase
{
    public function test_translation_keys_exist_in_all_languages(): void
    {
        $locales = ['es', 'en', 'fr', 'de', 'pt'];

        foreach ($locales as $locale) {
            app()->setLocale($locale);
            $msg = __('activities.unauthorized_status_change');
            $this->assertNotEmpty($msg, "Translation for {$locale} should not be empty");
            $this->assertNotEquals('activities.unauthorized_status_change', $msg, "Translation for {$locale} is missing");
        }

        app()->setLocale('es');
    }

    public function test_unauthorized_change_status_redirects_back_with_error_toast_for_web_request(): void
    {
        $user = new User(['id' => 999]);
        $this->actingAs($user);

        $team = new Team(['id' => 1, 'name' => 'Demo Team']);
        $activity = new Activity([
            'id' => 42,
            'team_id' => 1,
            'created_by_id' => 1, // Created by someone else
            'title' => 'Test Document',
            'type' => 'document',
            'status' => ['value' => 'draft'],
        ]);

        // Explicitly deny changeStatus
        Gate::before(fn () => false);

        $service = $this->createMock(ActivityService::class);
        $service->expects($this->never())->method('changeStatus');

        $controller = new ActivityController($service);

        $request = Request::create('/teams/1/activities/42/status', 'PATCH', [
            'status' => 'approved',
        ]);
        $request->setLaravelSession(app('session.store'));

        $response = $controller->changeStatus($request, $team, $activity);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(
            __('activities.unauthorized_status_change'),
            $request->session()->get('error')
        );
    }

    public function test_unauthorized_change_status_returns_json_error_for_ajax_request(): void
    {
        $user = new User(['id' => 999]);
        $this->actingAs($user);

        $team = new Team(['id' => 1, 'name' => 'Demo Team']);
        $activity = new Activity([
            'id' => 42,
            'team_id' => 1,
            'created_by_id' => 1,
            'title' => 'Test Document',
            'type' => 'document',
            'status' => ['value' => 'draft'],
        ]);

        Gate::before(fn () => false);

        $service = $this->createMock(ActivityService::class);
        $service->expects($this->never())->method('changeStatus');

        $controller = new ActivityController($service);

        $request = Request::create('/teams/1/activities/42/status', 'PATCH', [
            'status' => 'approved',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = $controller->changeStatus($request, $team, $activity);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals(__('activities.unauthorized_status_change'), $data['message']);
    }

    public function test_unauthorized_archive_and_unarchive_redirect_with_error(): void
    {
        $user = new User(['id' => 999]);
        $this->actingAs($user);

        $team = new Team(['id' => 1, 'name' => 'Demo Team']);
        $activity = new Activity(['id' => 42, 'team_id' => 1, 'created_by_id' => 1]);

        Gate::before(fn () => false);

        $service = $this->createMock(ActivityService::class);
        $controller = new ActivityController($service);

        $request = Request::create('/teams/1/activities/42/archive', 'POST');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        $archiveResponse = $controller->archive($team, $activity);
        $this->assertInstanceOf(RedirectResponse::class, $archiveResponse);
        $this->assertEquals(__('teams.unauthorized_access'), $request->session()->get('error'));

        $unarchiveResponse = $controller->unarchive($team, $activity);
        $this->assertInstanceOf(RedirectResponse::class, $unarchiveResponse);
        $this->assertEquals(__('teams.unauthorized_access'), $request->session()->get('error'));
    }
}
