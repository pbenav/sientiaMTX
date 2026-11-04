<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\Team;
use App\Models\User;

class ActivityTimerTest extends TestCase
{
    public function test_task_timer_button_renders_safely_with_relative_route_and_headers(): void
    {
        $team = new Team();
        $team->id = 18;

        $activity = new Activity();
        $activity->id = 2534;
        $activity->team_id = 18;
        $activity->title = 'Test Activity 2534';
        $activity->type = 'task';
        $activity->status = 'pending';

        $rendered = view('tasks.partials.task-timer-button', ['task' => $activity])->render();

        // Must use safe Alpine store access
        $this->assertStringContainsString("Alpine.store('timer').activeTaskId", $rendered);
        $this->assertStringContainsString("Alpine.store('timer')?.elapsed", $rendered);
        $this->assertStringContainsString("Alpine.store('timer')?.timer", $rendered);

        // Must use relative route with toggle-task/2534
        $this->assertStringContainsString('/time-logs/toggle-task/2534', $rendered);
        $this->assertStringNotContainsString('http://localhost', $rendered);

        // Must send Accept and X-Requested-With headers
        $this->assertStringContainsString("'Accept': 'application/json'", $rendered);
        $this->assertStringContainsString("'X-Requested-With': 'XMLHttpRequest'", $rendered);
    }

    public function test_activity_timer_button_renders_safely(): void
    {
        $activity = new Activity();
        $activity->id = 2534;
        $activity->team_id = 18;
        $activity->title = 'Test Activity 2534';
        $activity->type = 'task';

        $rendered = view('teams.activities.partials.activity-timer-button', ['activity' => $activity])->render();

        $this->assertStringContainsString("Alpine.store('timer').activeTaskId", $rendered);
        $this->assertStringContainsString('/time-logs/toggle-task/2534', $rendered);
        $this->assertStringContainsString("'Accept': 'application/json'", $rendered);
    }

    public function test_kanban_task_card_uses_safe_timer_store_accessor(): void
    {
        $team = new Team();
        $team->id = 18;

        $activity = new Activity();
        $activity->id = 2534;
        $activity->team_id = 18;
        $activity->title = 'Test Activity 2534';
        $activity->type = 'task';
        $activity->status = 'pending';

        $rendered = view('components.kanban.task-card', [
            'task' => $activity,
            'team' => $team,
            'column' => null,
            'quadrantConfig' => [],
            'showProgress' => false,
            'showBadges' => false,
            'showFooter' => false,
        ])->render();

        $this->assertStringContainsString("Alpine.store('timer')?.activeTaskId == this.taskId", $rendered);
    }

    public function test_markdown_editor_has_no_empty_alpine_event_handlers(): void
    {
        $rendered = view('components.markdown-editor', [
            'name' => 'description',
            'id' => 'desc',
            'value' => '',
        ])->render();

        // Ensure no empty string expressions like @wheel.stop="" or @dragover.prevent=""
        $this->assertStringNotContainsString('@wheel.stop=""', $rendered);
        $this->assertStringNotContainsString('@dragover.prevent=""', $rendered);
        $this->assertStringNotContainsString('\"Completados y Firmados\"', $rendered);
    }
}
