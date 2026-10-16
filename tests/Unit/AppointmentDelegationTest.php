<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\AppointmentDelegation;
use App\Models\AppointmentService;
use App\Models\Team;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Http\Middleware\EnsureAppointmentsAreEnabled;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppointmentDelegationTest extends TestCase
{
    public function test_appointment_delegation_model_fillable_and_relations(): void
    {
        $delegation = new AppointmentDelegation([
            'team_id'     => 1,
            'user_id'     => 10,
            'delegate_id' => 20,
        ]);

        $this->assertEquals(1, $delegation->team_id);
        $this->assertEquals(10, $delegation->user_id);
        $this->assertEquals(20, $delegation->delegate_id);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $delegation->team());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $delegation->user());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $delegation->delegate());
    }

    public function test_appointment_policy_authorizes_owner(): void
    {
        $owner = new User();
        $owner->id = 10;

        $service = new AppointmentService();
        $service->team_id = 1;

        $appointment = new Appointment([
            'user_id' => 10,
        ]);
        $appointment->setRelation('service', $service);

        $policy = new AppointmentPolicy();

        $this->assertTrue($policy->view($owner, $appointment));
        $this->assertTrue($policy->update($owner, $appointment));
        $this->assertTrue($policy->delete($owner, $appointment));
    }

    public function test_appointment_policy_denies_unauthorized_user(): void
    {
        $otherUser = $this->getMockBuilder(User::class)
            ->onlyMethods(['isSecretaryOf'])
            ->getMock();
        $otherUser->id = 99;
        $otherUser->method('isSecretaryOf')->willReturn(false);

        $service = new AppointmentService();
        $service->team_id = 1;

        $appointment = new Appointment([
            'user_id' => 10,
        ]);
        $appointment->setRelation('service', $service);

        $policy = new AppointmentPolicy();

        $this->assertFalse($policy->view($otherUser, $appointment));
        $this->assertFalse($policy->update($otherUser, $appointment));
        $this->assertFalse($policy->delete($otherUser, $appointment));
    }

    public function test_appointment_policy_authorizes_secretary(): void
    {
        $secretary = $this->getMockBuilder(User::class)
            ->onlyMethods(['isSecretaryOf'])
            ->getMock();
        $secretary->id = 20;
        $secretary->expects($this->atLeastOnce())
            ->method('isSecretaryOf')
            ->with(10, 1)
            ->willReturn(true);

        $service = new AppointmentService();
        $service->team_id = 1;

        $appointment = new Appointment([
            'user_id' => 10,
        ]);
        $appointment->setRelation('service', $service);

        $policy = new AppointmentPolicy();

        $this->assertTrue($policy->view($secretary, $appointment));
        $this->assertTrue($policy->update($secretary, $appointment));
        $this->assertTrue($policy->delete($secretary, $appointment));
    }

    public function test_middleware_allows_secretary_without_own_appointments(): void
    {
        $secretary = $this->getMockBuilder(User::class)
            ->onlyMethods(['canAccessAppointmentsForTeam'])
            ->getMock();
        $secretary->id = 20;
        $secretary->expects($this->once())
            ->method('canAccessAppointmentsForTeam')
            ->with(1)
            ->willReturn(true);

        $team = new Team(['name' => 'Demo Team']);
        $team->id = 1;

        $request = Request::create('/teams/1/mis-citas');
        $request->setUserResolver(fn() => $secretary);
        $request->setRouteResolver(function() use ($request, $team) {
            $route = new \Illuminate\Routing\Route('GET', '/teams/{team}/mis-citas', []);
            $route->bind($request);
            $route->setParameter('team', $team);
            return $route;
        });

        $middleware = new EnsureAppointmentsAreEnabled();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_blocks_user_without_access(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['canAccessAppointmentsForTeam'])
            ->getMock();
        $user->id = 55;
        $user->expects($this->once())
            ->method('canAccessAppointmentsForTeam')
            ->with(1)
            ->willReturn(false);

        $team = new Team(['name' => 'Demo Team']);
        $team->id = 1;

        $request = Request::create('/teams/1/mis-citas');
        $request->setUserResolver(fn() => $user);
        $request->setRouteResolver(function() use ($request, $team) {
            $route = new \Illuminate\Routing\Route('GET', '/teams/{team}/mis-citas', []);
            $route->bind($request);
            $route->setParameter('team', $team);
            return $route;
        });

        $middleware = new EnsureAppointmentsAreEnabled();
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
    }
}
