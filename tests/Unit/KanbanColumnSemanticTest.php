<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\KanbanColumn;
use App\Models\Activity;
use App\Models\Team;

class KanbanColumnSemanticTest extends TestCase
{
    public function test_kanban_column_semantic_helpers_detect_done_columns(): void
    {
        $col1 = new KanbanColumn(['title' => 'COMPLETADA', 'type' => 'custom']);
        $this->assertTrue($col1->isDone());
        $this->assertFalse($col1->isTodo());
        $this->assertTrue($col1->isDefaultSemantic());

        $col2 = new KanbanColumn(['title' => 'Completadas', 'type' => 'custom']);
        $this->assertTrue($col2->isDone());

        $col3 = new KanbanColumn(['title' => 'Hecho', 'type' => 'custom']);
        $this->assertTrue($col3->isDone());

        $col4 = new KanbanColumn(['title' => 'Finalizada', 'type' => 'custom']);
        $this->assertTrue($col4->isDone());

        $col5 = new KanbanColumn(['title' => 'Cualquier Cosa', 'type' => 'done']);
        $this->assertTrue($col5->isDone());
    }

    public function test_kanban_column_semantic_helpers_detect_todo_columns(): void
    {
        $col1 = new KanbanColumn(['title' => 'PENDIENTE', 'type' => 'custom']);
        $this->assertTrue($col1->isTodo());
        $this->assertFalse($col1->isDone());
        $this->assertTrue($col1->isDefaultSemantic());

        $col2 = new KanbanColumn(['title' => 'Por hacer', 'type' => 'custom']);
        $this->assertTrue($col2->isTodo());
    }

    public function test_kanban_column_semantic_helpers_detect_in_progress_columns(): void
    {
        $col1 = new KanbanColumn(['title' => 'EN PROGRESO', 'type' => 'custom']);
        $this->assertTrue($col1->isInProgress());
        $this->assertFalse($col1->isDone());
        $this->assertTrue($col1->isDefaultSemantic());

        $col2 = new KanbanColumn(['title' => 'En curso', 'type' => 'custom']);
        $this->assertTrue($col2->isInProgress());
    }

    public function test_kanban_column_custom_non_semantic_columns(): void
    {
        $col = new KanbanColumn(['title' => 'Revisión externa', 'type' => 'custom']);
        $this->assertFalse($col->isDone());
        $this->assertFalse($col->isTodo());
        $this->assertFalse($col->isInProgress());
        $this->assertFalse($col->isDefaultSemantic());
    }

    public function test_column_view_renders_archive_all_button_on_done_column(): void
    {
        $team = new Team();
        $team->id = 1;

        $column = new KanbanColumn(['title' => 'COMPLETADA', 'type' => 'custom']);
        $column->id = 3;

        $activity = new Activity([
            'title' => 'Tarea completada',
            'is_archived' => false,
            'status' => ['value' => 'completed'],
        ]);
        $activity->id = 100;
        $activity->setRelation('timeLogs', collect());
        $activity->setRelation('children', collect());
        $activity->setRelation('instances', collect());
        $activity->setRelation('attachments', collect());
        $activity->setRelation('assignedUser', null);
        $activity->setRelation('assignedTo', collect());
        $activity->setRelation('skills', collect());
        $activity->setRelation('tags', collect());
        $activity->setRelation('expediente', null);
        $activity->setRelation('notes', collect());

        $column->setRelation('activities', collect([$activity]));

        $rendered = view('components.kanban.column', [
            'column' => $column,
            'team' => $team,
            'quadrantConfig' => [],
        ])->render();

        // Must render archive all button because it is a Done column with non-archived activities
        $this->assertStringContainsString('archiveAllCompleted(3)', $rendered);
        // Must NOT render trash delete button for default semantic Done column
        $this->assertStringNotContainsString('deleteColumn(3)', $rendered);
    }

    public function test_column_view_does_not_render_archive_all_button_on_todo_column(): void
    {
        $team = new Team();
        $team->id = 1;

        $column = new KanbanColumn(['title' => 'PENDIENTE', 'type' => 'todo']);
        $column->id = 1;

        $activity = new Activity([
            'title' => 'Tarea pendiente',
            'is_archived' => false,
            'status' => ['value' => 'pending'],
        ]);
        $activity->id = 101;
        $activity->setRelation('timeLogs', collect());
        $activity->setRelation('children', collect());
        $activity->setRelation('instances', collect());
        $activity->setRelation('attachments', collect());
        $activity->setRelation('assignedUser', null);
        $activity->setRelation('assignedTo', collect());
        $activity->setRelation('skills', collect());
        $activity->setRelation('tags', collect());
        $activity->setRelation('expediente', null);
        $activity->setRelation('notes', collect());

        $column->setRelation('activities', collect([$activity]));

        $rendered = view('components.kanban.column', [
            'column' => $column,
            'team' => $team,
            'quadrantConfig' => [],
        ])->render();

        // Must NOT render archive all button on PENDIENTE
        $this->assertStringNotContainsString('archiveAllCompleted', $rendered);
    }
}
