<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\ForumThread;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ForumThreadAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subtask_assignee_can_access_root_activity_forum_thread()
    {
        $owner = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team ' . Str::random(5),
            'slug' => 'test-team-' . Str::random(5),
            'created_by_id' => $owner->id,
        ]);
        $team->members()->attach($owner->id, ['role_id' => 3]);

        $member = User::factory()->create();
        $team->members()->attach($member->id, ['role_id' => 3]);

        // Root activity owned by owner, with semi-private visibility
        $rootActivity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'visibility' => 'semi-private',
            'type' => 'task',
            'title' => 'Root Task',
        ]);

        // Forum thread attached to root activity
        $thread = ForumThread::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'activity_id' => $rootActivity->id,
            'title' => 'Discusión: Root Task',
        ]);

        // Child subtask
        $childActivity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'parent_id' => $rootActivity->id,
            'visibility' => 'semi-private',
            'type' => 'task',
            'title' => 'Subtask 1',
        ]);

        // Initially, member is NOT assigned to either root or child
        $this->assertFalse($thread->canUserAccess($member, $team));

        // Assign member to child activity
        $childActivity->assignedTo()->attach($member->id, [
            'assigned_by_id' => $owner->id,
            'assigned_at' => now(),
        ]);

        // Refresh relations and test
        $rootActivity->unsetRelation('children');
        $this->assertTrue($thread->canUserAccess($member, $team));
    }

    public function test_parent_assignee_can_access_child_activity_forum_thread()
    {
        $owner = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team ' . Str::random(5),
            'slug' => 'test-team-' . Str::random(5),
            'created_by_id' => $owner->id,
        ]);
        $team->members()->attach($owner->id, ['role_id' => 3]);

        $member = User::factory()->create();
        $team->members()->attach($member->id, ['role_id' => 3]);

        $parentActivity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'visibility' => 'semi-private',
            'type' => 'task',
            'title' => 'Parent Project',
        ]);

        $childActivity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'parent_id' => $parentActivity->id,
            'visibility' => 'semi-private',
            'type' => 'task',
            'title' => 'Child Step',
        ]);

        $thread = ForumThread::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'activity_id' => $childActivity->id,
            'title' => 'Discusión: Child Step',
        ]);

        $this->assertFalse($thread->canUserAccess($member, $team));

        // Assign member to parent activity
        $parentActivity->assignedTo()->attach($member->id, [
            'assigned_by_id' => $owner->id,
            'assigned_at' => now(),
        ]);

        $childActivity->unsetRelation('parent');
        $this->assertTrue($thread->canUserAccess($member, $team));
    }

    public function test_converted_activity_assignee_can_access_original_thread()
    {
        $owner = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team ' . Str::random(5),
            'slug' => 'test-team-' . Str::random(5),
            'created_by_id' => $owner->id,
        ]);
        $team->members()->attach($owner->id, ['role_id' => 3]);

        $member = User::factory()->create();
        $team->members()->attach($member->id, ['role_id' => 3]);

        $originalActivity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'visibility' => 'semi-private',
            'type' => 'reminder',
            'title' => 'Original Reminder',
        ]);

        $convertedActivity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'converted_from_id' => $originalActivity->id,
            'visibility' => 'semi-private',
            'type' => 'task',
            'title' => 'Converted Task',
        ]);

        $thread = ForumThread::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'activity_id' => $originalActivity->id,
            'title' => 'Discusión: Reminder',
        ]);

        $this->assertFalse($thread->canUserAccess($member, $team));

        // Assign member to converted activity
        $convertedActivity->assignedTo()->attach($member->id, [
            'assigned_by_id' => $owner->id,
            'assigned_at' => now(),
        ]);

        $originalActivity->unsetRelation('convertedToActivity');
        $this->assertTrue($thread->canUserAccess($member, $team));
    }

    public function test_coordinator_can_access_thread_even_if_not_owner_or_assigned()
    {
        $owner = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team ' . Str::random(5),
            'slug' => 'test-team-' . Str::random(5),
            'created_by_id' => $owner->id,
        ]);
        $team->members()->attach($owner->id, ['role_id' => 3]);

        $coordinator = User::factory()->create();
        $team->members()->attach($coordinator->id, ['role_id' => 1]); // coordinator

        $activity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'visibility' => 'semi-private',
            'type' => 'task',
            'title' => 'Team Task',
        ]);

        $thread = ForumThread::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'activity_id' => $activity->id,
            'title' => 'Discusión: Team Task',
        ]);

        // Coordinator is neither owner nor assigned, but should have access to semi-private thread
        $this->assertTrue($thread->canUserAccess($coordinator, $team));
    }

    public function test_deep_privacy_blocks_unauthorized_member_on_private_thread()
    {
        $owner = User::factory()->create();
        $team = Team::create([
            'name' => 'Test Team ' . Str::random(5),
            'slug' => 'test-team-' . Str::random(5),
            'created_by_id' => $owner->id,
        ]);
        $team->members()->attach($owner->id, ['role_id' => 3]);

        $outsider = User::factory()->create();
        $team->members()->attach($outsider->id, ['role_id' => 3]);

        $activity = Activity::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $team->id,
            'created_by_id' => $owner->id,
            'visibility' => 'private',
            'type' => 'task',
            'title' => 'Secret Task',
        ]);

        $thread = ForumThread::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'activity_id' => $activity->id,
            'title' => 'Confidential Thread',
        ]);

        $this->assertFalse($thread->canUserAccess($outsider, $team));
    }
}
