<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\ForumThread;

class ForumThreadActivityTest extends TestCase
{
    public function test_forum_thread_fillable_and_relations(): void
    {
        $thread = new ForumThread([
            'team_id' => 1,
            'user_id' => 2,
            'activity_id' => 3,
            'title' => 'Test Thread',
        ]);

        $this->assertEquals(3, $thread->activity_id);
        $this->assertFalse($thread->isKnowledgeLibrary());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->activity());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->task());
    }

    public function test_activity_has_forum_thread_relation(): void
    {
        $activity = new Activity();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $activity->forumThread());
    }
}

