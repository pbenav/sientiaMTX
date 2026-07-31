<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case BLOCKED = 'blocked';

    // Alternate semantic states
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PROPOSED = 'proposed';
    case TODO = 'todo';
    case UNDER_REVIEW = 'under_review';
    case IN_DEBATE = 'in_debate';
    case APPROVED = 'approved';
    case TRIGGERED = 'triggered';
    case ACCEPTED = 'accepted';
    case FINISHED = 'finished';

    public function isCompleted(): bool
    {
        return in_array($this->value, [
            'completed', 'done', 'approved', 'triggered', 'accepted', 'finished',
        ]);
    }

    public function isPending(): bool
    {
        return in_array($this->value, [
            'pending', 'draft', 'scheduled', 'proposed', 'todo',
        ]);
    }

    public static function kanbanStatuses(): array
    {
        return [
            self::PENDING->value,
            self::IN_PROGRESS->value,
            self::COMPLETED->value,
            self::CANCELLED->value,
            self::BLOCKED->value,
        ];
    }
}
