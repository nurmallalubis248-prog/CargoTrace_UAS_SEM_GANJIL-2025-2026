<?php

namespace App\Enums;

enum ExceptionStatus: string
{
    case OPEN = 'OPEN';
    case INVESTIGATING = 'INVESTIGATING';
    case RESOLVED = 'RESOLVED';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::OPEN => [self::INVESTIGATING, self::RESOLVED],
            self::INVESTIGATING => [self::RESOLVED],
            self::RESOLVED => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return $next === $this || in_array($next, $this->allowedNext(), true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OPEN => 'bg-red-100 text-red-700',
            self::INVESTIGATING => 'bg-amber-100 text-amber-700',
            self::RESOLVED => 'bg-matcha-100 text-matcha-800',
        };
    }
}
