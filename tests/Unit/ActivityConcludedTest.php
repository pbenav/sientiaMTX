<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use Carbon\Carbon;

class ActivityConcludedTest extends TestCase
{
    public function test_document_concluded_states(): void
    {
        $draftDoc = new Activity(['type' => 'document', 'status' => ['value' => 'draft']]);
        $this->assertFalse($draftDoc->isConcluded());

        $publishedDoc = new Activity(['type' => 'document', 'status' => ['value' => 'published']]);
        $this->assertTrue($publishedDoc->isConcluded());

        $approvedDoc = new Activity(['type' => 'document', 'status' => ['value' => 'approved']]);
        $this->assertTrue($approvedDoc->isConcluded());

        $archivedDoc = new Activity(['type' => 'document', 'status' => ['value' => 'archived']]);
        $this->assertTrue($archivedDoc->isConcluded());
    }

    public function test_task_concluded_states(): void
    {
        $pendingTask = new Activity(['type' => 'task', 'status' => ['value' => 'pending']]);
        $this->assertFalse($pendingTask->isConcluded());

        $completedTask = new Activity(['type' => 'task', 'status' => ['value' => 'completed']]);
        $this->assertTrue($completedTask->isConcluded());

        $completedTemplate = new Activity(['type' => 'task', 'is_template' => true, 'status' => ['value' => 'completed']]);
        $this->assertTrue($completedTemplate->isConcluded());

        $cancelledTask = new Activity(['type' => 'task', 'status' => ['value' => 'cancelled']]);
        $this->assertTrue($cancelledTask->isConcluded());
    }

    public function test_archived_flag_makes_any_activity_concluded(): void
    {
        $archivedActivity = new Activity(['type' => 'task', 'is_archived' => true, 'status' => ['value' => 'pending']]);
        $this->assertTrue($archivedActivity->isConcluded());
    }

    public function test_reminder_concluded_states(): void
    {
        // Future reminder is active
        $futureReminder = new Activity([
            'type' => 'reminder',
            'status' => ['value' => 'pending'],
            'due_date' => Carbon::now()->addDay(),
        ]);
        $this->assertFalse($futureReminder->isConcluded());

        // Past reminder without repeat is concluded
        $pastReminder = new Activity([
            'type' => 'reminder',
            'status' => ['value' => 'pending'],
            'due_date' => Carbon::now()->subDay(),
            'metadata' => ['repeat' => false],
        ]);
        $this->assertTrue($pastReminder->isConcluded());

        // Past reminder WITH repeat is still active
        $repeatingReminder = new Activity([
            'type' => 'reminder',
            'status' => ['value' => 'pending'],
            'due_date' => Carbon::now()->subDay(),
            'metadata' => ['repeat' => true],
        ]);
        $this->assertFalse($repeatingReminder->isConcluded());
    }

    public function test_link_and_note_concluded_states(): void
    {
        $this->assertContains('draft', \App\Models\Activities\LinkActivity::STATUSES);
        $this->assertContains('active', \App\Models\Activities\LinkActivity::STATUSES);

        $draftLink = new Activity(['type' => 'link', 'status' => ['value' => 'draft']]);
        $this->assertFalse($draftLink->isConcluded());

        $activeLink = new Activity(['type' => 'link', 'status' => ['value' => 'active']]);
        $this->assertTrue($activeLink->isConcluded());

        $completedLink = new Activity(['type' => 'link', 'status' => ['value' => 'completed']]);
        $this->assertTrue($completedLink->isConcluded());

        $brokenLink = new Activity(['type' => 'link', 'status' => ['value' => 'broken']]);
        $this->assertTrue($brokenLink->isConcluded());

        $draftNote = new Activity(['type' => 'note', 'status' => ['value' => 'draft']]);
        $this->assertFalse($draftNote->isConcluded());

        $finalNote = new Activity(['type' => 'note', 'status' => ['value' => 'final']]);
        $this->assertTrue($finalNote->isConcluded());
    }

    public function test_activity_link_helpers_work_on_base_activity(): void
    {
        $link = new Activity([
            'type' => 'link',
            'metadata' => [
                'url' => 'https://sientia.com',
                'og_title' => 'Sientia Platform',
                'og_image' => 'https://sientia.com/logo.png',
            ],
            'status' => ['value' => 'broken'],
        ]);

        $this->assertEquals('https://sientia.com', $link->getUrl());
        $this->assertEquals('Sientia Platform', $link->getOgTitle());
        $this->assertEquals('https://sientia.com/logo.png', $link->getOgImage());
        $this->assertTrue($link->isBroken());

        $linkWithLinksArray = new Activity([
            'type' => 'link',
            'metadata' => [
                'links' => [
                    ['title' => 'Documentación', 'url' => 'https://docs.sientia.com'],
                ],
            ],
        ]);

        $this->assertEquals('https://docs.sientia.com', $linkWithLinksArray->getUrl());
    }
}

