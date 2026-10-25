<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\Team;
use App\Models\User;

class KanbanScopeTest extends TestCase
{
    public function test_kanban_types_only_contains_actionable_types(): void
    {
        $this->assertEquals(['task', 'agreement'], Activity::KANBAN_TYPES);
        $this->assertNotContains('document', Activity::KANBAN_TYPES);
        $this->assertNotContains('meeting', Activity::KANBAN_TYPES);
        $this->assertNotContains('reminder', Activity::KANBAN_TYPES);
        $this->assertNotContains('note', Activity::KANBAN_TYPES);
        $this->assertNotContains('link', Activity::KANBAN_TYPES);
    }

    public function test_operational_for_kanban_sql_enforces_team_isolation(): void
    {
        $team = new Team();
        $team->id = 42;

        $user = new User();
        $user->id = 7;

        $query = Activity::query()->operationalForKanban($user, $team);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Must enforce team_id = 42
        $this->assertTrue(
            str_contains($sql, '"activities"."team_id" = ?') || str_contains($sql, '`activities`.`team_id` = ?')
        );
        $this->assertContains(42, $bindings);

        // Must exclude templates
        $this->assertTrue(
            str_contains($sql, '"activities"."is_template" = ?') || str_contains($sql, '`activities`.`is_template` = ?')
        );

        // Must check assigned user via activity_assignments pivot
        $this->assertTrue(
            str_contains($sql, 'activity_assignments')
        );
        $this->assertContains(7, $bindings);
    }

    public function test_operational_for_team_enforces_team_isolation_and_excludes_templates(): void
    {
        $team = new Team();
        $team->id = 99;

        $query = Activity::query()->operationalForTeam($team);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertTrue(
            str_contains($sql, '"activities"."team_id" = ?') || str_contains($sql, '`activities`.`team_id` = ?')
        );
        $this->assertContains(99, $bindings);

        $this->assertTrue(
            str_contains($sql, '"activities"."is_template" = ?') || str_contains($sql, '`activities`.`is_template` = ?')
        );
    }
}

