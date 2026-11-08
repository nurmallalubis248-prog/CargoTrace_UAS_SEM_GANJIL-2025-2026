<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;

/** Builds the data behind the Reports page and the PDF. Customers only ever get their own shipments. */
class ReportService
{
    public const MAX_ROWS = 500;

    public function build(User $user, array $filters): array
    {
        $query = fn () => Shipment::query()->visibleTo($user)->filter($filters);

        $total = $query()->count();
        $notCancelled = $query()->where('shipments.current_status', '!=', ShipmentStatus::CANCELLED->value)->count();
        $delayed = $query()->where('shipments.is_delayed', true)
            ->where('shipments.current_status', '!=', ShipmentStatus::CANCELLED->value)->count();
        $ids = fn () => $query()->select('shipments.id');

        $rows = $query()
            ->with(['customer', 'carrier', 'origin', 'destination', 'exceptions'])
            ->orderByDesc('shipments.created_at')->orderByDesc('shipments.id')
            ->limit(self::MAX_ROWS)
            ->get();

        return [
            'generated_at' => now(),
            'total' => $total,
            'truncated' => $total > self::MAX_ROWS,
            'rows' => $rows,
            'summary' => [
                'total' => $total,
                'in_transit' => $query()->where('shipments.current_status', ShipmentStatus::IN_TRANSIT->value)->count(),
                'delivered' => $query()->where('shipments.current_status', ShipmentStatus::DELIVERED->value)->count(),
                'delayed' => $delayed,
                'open_exceptions' => ShipmentException::query()->open()->whereIn('shipment_id', $ids())->count(),
                'delay_rate_pct' => $notCancelled ? round($delayed / $notCancelled * 100, 1) : 0.0,
                'revenue' => (int) Payment::where('status', 'PAID')->whereIn('shipment_id', $ids())->sum('amount'),
            ],
        ];
    }
}
