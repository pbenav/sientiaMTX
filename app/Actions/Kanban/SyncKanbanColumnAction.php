<?php

namespace App\Actions\Kanban;

use App\Models\Activity;
use App\Models\KanbanColumn;
use App\Models\Team;

class SyncKanbanColumnAction
{
    /**
     * Syncs activity status/progress based on the target Kanban column.
     *
     * @param Activity $task
     * @param KanbanColumn $column
     * @param bool $logHistory Whether to log the column change history
     * @param int|null $oldColumnId
     * @return array{status: string, progress: int}
     */
    public function execute(Activity $task, KanbanColumn $column, bool $logHistory = false, ?int $oldColumnId = null): array
    {
        $oldStatus = $task->status_value;
        $oldColumnId = $oldColumnId ?? $task->kanban_column_id;

        $this->syncProgressAndStatus($task, $column);
        $task->save();

        // Log history if column changed
        if ($logHistory && $oldColumnId != $task->kanban_column_id) {
            $task->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'moved_column',
                'old_values' => ['kanban_column_id' => $oldColumnId],
                'new_values' => [
                    'kanban_column_id' => $task->kanban_column_id,
                    'status' => $task->status,
                    'progress' => $task->progress_percentage,
                ],
            ]);
        }

        // Gamification: Award points if newly completed
        if ($task->isCompleted() && !in_array($oldStatus, ['completed', 'done', 'approved', 'triggered', 'accepted', 'finished'])) {
            try {
                app(GamificationRewardAction::class)->execute($task);
                $task->notifyCoordinatorsIfCompleted();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Kanban Gamification/Notification error: " . $e->getMessage());
            }
        }

        // Parent sync
        $this->syncParentProgress($task);

        return [
            'status' => $task->status_value,
            'progress' => $task->progress_percentage,
        ];
    }

    /**
     * Updates progress and status based on column type.
     */
    protected function syncProgressAndStatus(Activity $task, KanbanColumn $column): void
    {
        match ($column->type) {
            'todo' => $this->setTodoState($task),
            'done' => $this->setDoneState($task),
            'in_progress', 'custom' => $this->setInProgressState($task, $column),
            default => null,
        };
    }

    /**
     * Sets the task to pending/draft state with 0% progress.
     */
    protected function setTodoState(Activity $task): void
    {
        $task->progress_percentage = 0;
        $task->status = ['value' => $this->statusForType($task, 'pending', 'draft', 'proposed', 'scheduled')];
    }

    /**
     * Sets the task to completed/approved state with 100% progress.
     */
    protected function setDoneState(Activity $task): void
    {
        $task->progress_percentage = 100;
        $task->status = ['value' => $this->statusForType($task, 'completed', 'approved', 'accepted', 'finished')];
    }

    /**
     * Sets the task to in_progress state with intermediate progress.
     */
    protected function setInProgressState(Activity $task, KanbanColumn $column): void
    {
        if ($column->default_progress !== null) {
            $task->progress_percentage = $column->default_progress;
        } else {
            if ($task->progress_percentage == 0) {
                $task->progress_percentage = 10;
            } elseif ($task->progress_percentage == 100) {
                $task->progress_percentage = 90;
            }
        }

        if ($task->isCompleted() || $task->isPending()) {
            $task->status = ['value' => $this->statusForType($task, 'in_progress', 'under_review', 'in_debate', 'in_progress')];
        }
    }

    /**
     * Returns the appropriate status value based on activity type.
     */
    protected function statusForType(Activity $task, string $default, string $document, string $agreement, string $meeting): string
    {
        return match ($task->type) {
            'document' => $document,
            'agreement' => $agreement,
            'meeting' => $meeting,
            default => $default,
        };
    }

    /**
     * Propagates progress upward through parent hierarchy.
     */
    protected function syncParentProgress(Activity $task): void
    {
        $parent = $task->parent;
        while ($parent) {
            $parent->update(['progress_percentage' => $parent->progress]);
            $parent->syncKanbanColumn();
            $parent = $parent->parent;
        }
    }
}
