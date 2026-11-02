<?php

namespace App\Enums;

enum Severity: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'bg-yellow-100 text-yellow-800',
            self::MEDIUM => 'bg-sakura-100 text-sakura-700',
            self::HIGH, self::CRITICAL => 'bg-red-100 text-red-800',
        };
    }
}
