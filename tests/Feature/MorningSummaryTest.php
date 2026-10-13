<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Team;
use App\Models\Activity;
use App\Notifications\MorningSummaryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class MorningSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'notification_settings' => [
                'morning_summary' => true,
                'morning_summary_time' => '08:00',
                'morning_summary_weekends' => true,
            ],
        ]);

        $this->team = Team::create([
            'name' => 'Test Team',
            'user_id' => $this->user->id,
            'personal_team' => false,
        ]);

        $this->team->users()->attach($this->user, ['role' => 'coordinator']);
    }

    public function test_in_quadrant_scope_correctly_categorizes_activities()
    {
        // Q1: High priority, High urgency
        $q1 = Activity::create([
            'title' => 'Q1 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'high',
            'metadata' => ['urgency' => 'high'],
        ]);

        // Q2: High priority, Low/Medium urgency
        $q2 = Activity::create([
            'title' => 'Q2 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'critical',
            'metadata' => ['urgency' => 'medium'],
        ]);

        // Q3: Low/Medium priority, High urgency
        $q3 = Activity::create([
            'title' => 'Q3 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'low',
            'metadata' => ['urgency' => 'critical'],
        ]);

        // Q4: Low/Medium priority, Low/Medium urgency
        $q4 = Activity::create([
            'title' => 'Q4 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'medium',
            'metadata' => ['urgency' => 'medium'],
        ]);

        $this->assertTrue(Activity::inQuadrant(1)->pluck('id')->contains($q1->id));
        $this->assertFalse(Activity::inQuadrant(1)->pluck('id')->contains($q2->id));
        $this->assertFalse(Activity::inQuadrant(1)->pluck('id')->contains($q3->id));
        $this->assertFalse(Activity::inQuadrant(1)->pluck('id')->contains($q4->id));

        $this->assertTrue(Activity::inQuadrant(2)->pluck('id')->contains($q2->id));
        $this->assertTrue(Activity::inQuadrant(3)->pluck('id')->contains($q3->id));
        $this->assertTrue(Activity::inQuadrant(4)->pluck('id')->contains($q4->id));
    }

    public function test_morning_focus_scope_includes_q1_due_today_and_scheduled_today()
    {
        $today = Carbon::today();

        // 1. Q1 Task (urgente e importante)
        $q1Task = Activity::create([
            'title' => 'Q1 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'high',
            'metadata' => ['urgency' => 'high'],
        ]);

        // 2. Task due today (independientemente de urgencia)
        $dueTodayTask = Activity::create([
            'title' => 'Due Today Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'medium',
            'due_date' => $today->copy()->addHours(12),
        ]);

        // 3. Q2 Task scheduled for today
        $scheduledTodayTask = Activity::create([
            'title' => 'Scheduled Today Q2 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'high',
            'scheduled_date' => $today,
        ]);

        // 4. Q4 Task without scheduled date (DEBE SER EXCLUIDA - problema anterior)
        $q4Task = Activity::create([
            'title' => 'Q4 Unscheduled Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'low',
            'metadata' => ['urgency' => 'low'],
            'scheduled_date' => null,
        ]);

        // 5. Q3 Task without imminent due date (DEBE SER EXCLUIDA)
        $q3Task = Activity::create([
            'title' => 'Q3 Task Distant Due',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'low',
            'metadata' => ['urgency' => 'high'],
            'due_date' => $today->copy()->addDays(15),
        ]);

        // 6. Q1 Task deferred to the future (DEBE SER EXCLUIDA)
        $futureQ1Task = Activity::create([
            'title' => 'Future Q1 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'critical',
            'metadata' => ['urgency' => 'critical'],
            'scheduled_date' => $today->copy()->addDays(5),
        ]);

        // 7. Q4 Task overdue in the past (DEBE SER EXCLUIDA)
        $overdueQ4Task = Activity::create([
            'title' => 'Overdue Q4 Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'low',
            'metadata' => ['urgency' => 'low'],
            'due_date' => $today->copy()->subDays(10),
        ]);

        $focusedIds = Activity::morningFocus($today)->pluck('id')->toArray();

        $this->assertContains($q1Task->id, $focusedIds);
        $this->assertContains($dueTodayTask->id, $focusedIds);
        $this->assertContains($scheduledTodayTask->id, $focusedIds);

        $this->assertNotContains($q4Task->id, $focusedIds);
        $this->assertNotContains($q3Task->id, $focusedIds);
        $this->assertNotContains($futureQ1Task->id, $focusedIds);
        $this->assertNotContains($overdueQ4Task->id, $focusedIds);
    }

    public function test_send_morning_summary_command_sends_only_focused_tasks()
    {
        Notification::fake();

        // Tarea Q1 asignada al usuario
        $q1Task = Activity::create([
            'title' => 'Important Urgent Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'high',
            'metadata' => ['urgency' => 'high'],
        ]);
        $q1Task->assignedTo()->attach($this->user->id, [
            'assigned_by_id' => $this->user->id,
            'assigned_at' => now(),
        ]);

        // Tarea Q4 asignada al usuario (no debe enviarse)
        $q4Task = Activity::create([
            'title' => 'Eliminate Task',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'task',
            'priority' => 'low',
            'metadata' => ['urgency' => 'low'],
        ]);
        $q4Task->assignedTo()->attach($this->user->id, [
            'assigned_by_id' => $this->user->id,
            'assigned_at' => now(),
        ]);

        $this->artisan('morning:summary', ['--user' => $this->user->id])
            ->assertExitCode(0);

        Notification::assertSentTo(
            $this->user,
            MorningSummaryNotification::class,
            function ($notification) use ($q1Task, $q4Task) {
                $ref = new \ReflectionProperty($notification, 'tasks');
                $ref->setAccessible(true);
                $tasks = $ref->getValue($notification);

                return $tasks->contains('id', $q1Task->id) && !$tasks->contains('id', $q4Task->id);
            }
        );
    }
}
