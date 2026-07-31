<?php

namespace App\Enums;

enum AssignmentMode: string
{
    case INDIVIDUAL = 'individual';
    case SHARED = 'shared';

    public static function values(): array
    {
        return array_map(fn($s) => $s->value, self::cases());
    }
}
