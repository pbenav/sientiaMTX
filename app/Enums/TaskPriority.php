<?php

namespace App\Enums;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public static function orderQuery(): string
    {
        return "FIELD(priority, 'critical', 'high', 'medium', 'low') ASC";
    }

    public static function values(): array
    {
        return array_map(fn($s) => $s->value, self::cases());
    }
}
