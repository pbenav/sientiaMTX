<?php

namespace App\Enums;

enum TaskType: string
{
    case TEMPLATE = 'template';
    case INSTANCE = 'instance';
    case PLAIN = 'plain';

    public static function values(): array
    {
        return array_map(fn($s) => $s->value, self::cases());
    }
}
