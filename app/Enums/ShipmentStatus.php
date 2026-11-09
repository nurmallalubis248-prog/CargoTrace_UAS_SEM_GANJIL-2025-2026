<?php

namespace App\Enums;

/**
 * Shipment lifecycle state machine.
 * CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> ARRIVED -> DELIVERED
 * ARRIVED: the cargo reached the destination; it stays on the live map and the freight invoice is due.
 * DELIVERED: the customer paid and confirmed receipt (only then does the cargo leave the live map).
 * CANCELLED is only reachable from CREATED / BOOKED. DELIVERED and CANCELLED are terminal.
 */
enum ShipmentStatus: string
{
    case CREATED = 'CREATED';
    case BOOKED = 'BOOKED';
    case PICKED_UP = 'PICKED_UP';
    case IN_TRANSIT = 'IN_TRANSIT';
    case ARRIVED = 'ARRIVED';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::CREATED => [self::BOOKED, self::CANCELLED],
            self::BOOKED => [self::PICKED_UP, self::CANCELLED],
            self::PICKED_UP => [self::IN_TRANSIT],
            self::IN_TRANSIT => [self::ARRIVED],
            self::ARRIVED => [self::DELIVERED],
            self::DELIVERED, self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /** The freight can be paid at any active stage (not after DELIVERED / CANCELLED). Confirming receipt still needs ARRIVED + PAID. */
    public function isPayable(): bool
    {
        return in_array($this, [self::CREATED, self::BOOKED, self::PICKED_UP, self::IN_TRANSIT, self::ARRIVED], true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedNext() === [];
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::CREATED => 'bg-ink-100 text-ink-700',
            self::BOOKED => 'bg-sakura-100 text-sakura-700',
            self::PICKED_UP => 'bg-amber-100 text-amber-700',
            self::IN_TRANSIT => 'bg-matcha-100 text-matcha-800',
            self::ARRIVED => 'bg-sky-100 text-sky-800',
            self::DELIVERED => 'bg-matcha-600 text-white',
            self::CANCELLED => 'bg-red-50 text-red-700',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
