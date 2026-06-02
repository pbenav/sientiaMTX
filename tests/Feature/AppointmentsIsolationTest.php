<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Team;
use App\Models\AppointmentSettings;
use App\Models\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentsIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed settings or table defaults if required by base layout
        \DB::table('settings')->insertOrIgnore([
            ['key' => 'require_approval', 'value' => '0'],
        ]);
        $this->seed(\Database\Seeders\TeamRoleSeeder::class);
    }

    public function test_appointments_dashboard_requires_appointments_enabled_middleware()
    {
        $user = User::factory()->create(['privacy_policy_accepted_at' => now()]);
        $team = Team::create([
            'name' => 'Team A',
            'slug' => 'team-a',
            'created_by_id' => $user->id,
            'settings' => ['has_appointments' => true],
        ]);

        $role = \App\Models\TeamRole::where('name', 'user')->first();

        // Scenario 1: User is not in the team
        $response = $this->actingAs($user)->get(route('appointments.index', $team));
        $response->assertRedirect(route('dashboard'));

        // Scenario 2: User is in the team but allow_appointments is false
        $team->members()->attach($user, ['role_id' => $role->id, 'allow_appointments' => false]);
        $response = $this->actingAs($user)->get(route('appointments.index', $team));
        $response->assertRedirect(route('dashboard'));

        // Scenario 3: User is in the team and allow_appointments is true
        $team->members()->updateExistingPivot($user->id, ['allow_appointments' => true]);
        
        // Also ensure settings exist so the view doesn't throw null errors
        AppointmentSettings::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'public_slug' => 'team-a-portal',
            'is_public' => true,
        ]);

        $response = $this->actingAs($user)->get(route('appointments.index', $team));
        $response->assertStatus(200);
    }

    public function test_appointments_isolation_between_teams()
    {
        $user = User::factory()->create(['privacy_policy_accepted_at' => now()]);
        
        $teamA = Team::create([
            'name' => 'Team A',
            'slug' => 'team-a',
            'created_by_id' => $user->id,
            'settings' => ['has_appointments' => true],
        ]);
        
        $teamB = Team::create([
            'name' => 'Team B',
            'slug' => 'team-b',
            'created_by_id' => $user->id,
            'settings' => ['has_appointments' => true],
        ]);

        $role = \App\Models\TeamRole::where('name', 'user')->first();

        $teamA->members()->attach($user, ['role_id' => $role->id, 'allow_appointments' => true]);
        $teamB->members()->attach($user, ['role_id' => $role->id, 'allow_appointments' => true]);

        AppointmentSettings::create([
            'team_id' => $teamA->id,
            'user_id' => $user->id,
            'public_slug' => 'team-a-portal',
            'is_public' => true,
        ]);

        AppointmentSettings::create([
            'team_id' => $teamB->id,
            'user_id' => $user->id,
            'public_slug' => 'team-b-portal',
            'is_public' => true,
        ]);

        // Create a service for Team A
        $serviceA = AppointmentService::create([
            'team_id' => $teamA->id,
            'user_id' => $user->id,
            'name' => 'Service A',
            'duration_minutes' => 30,
            'max_parallel_appointments' => 1,
            'is_active' => true,
        ]);

        // Create a service for Team B
        $serviceB = AppointmentService::create([
            'team_id' => $teamB->id,
            'user_id' => $user->id,
            'name' => 'Service B',
            'duration_minutes' => 30,
            'max_parallel_appointments' => 1,
            'is_active' => true,
        ]);

        // User views services index for Team A, should only see Service A
        $response = $this->actingAs($user)->get(route('appointments.services.index', $teamA));
        $response->assertStatus(200);
        $response->assertSee('Service A');
        $response->assertDontSee('Service B');

        // User views services index for Team B, should only see Service B
        $response = $this->actingAs($user)->get(route('appointments.services.index', $teamB));
        $response->assertStatus(200);
        $response->assertSee('Service B');
        $response->assertDontSee('Service A');

        // Accessing Service B from Team A context should return 403
        $response = $this->actingAs($user)->get(route('appointments.services.edit', [$teamA, $serviceB]));
        $response->assertStatus(403);
    }
}
