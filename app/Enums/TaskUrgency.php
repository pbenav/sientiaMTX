<?php

namespace App\Enums;

enum TaskUrgency: string
{
    case VERY_LOW = 'very_low';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case VERY_HIGH = 'very_high';

    public function colorClass(): string
    {
        return match ($this) {
            self::VERY_HIGH  => 'text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/40',
            self::HIGH     => 'text-orange-700 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/40',
            self::MEDIUM   => 'text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/40',
            self::LOW      => 'text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/40',
            default        => 'text-gray-700 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/40',
        };
    }

    public static function values(): array
    {
        return array_map(fn($s) => $s->value, self::cases());
    }
}
