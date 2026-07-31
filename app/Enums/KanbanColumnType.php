<?php

namespace App\Enums;

enum KanbanColumnType: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case CUSTOM = 'custom';

    public static function defaultColors(): array
    {
        return [
            self::TODO->value => '#fee2e2',
            self::IN_PROGRESS->value => '#dbeafe',
            self::DONE->value => '#dcfce7',
        ];
    }

    public function defaultColor(): ?string
    {
        return self::defaultColors()[$this->value] ?? null;
    }

    public function isDefault(): bool
    {
        return in_array($this->value, [self::TODO->value, self::IN_PROGRESS->value, self::DONE->value]);
    }
}
