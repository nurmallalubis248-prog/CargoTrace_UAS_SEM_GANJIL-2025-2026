<?php

namespace App\Services;

use App\Models\Payment;

/** Revenue figures for the admin finance report (screen + PDF). Only PAID payments count as revenue. */
class FinanceReportService
{
    public const MAX_ROWS = 500;

    public function build(?string $from = null, ?string $to = null, ?string $payout = null): array
    {
        $base = fn () => Payment::query()->where('status', 'PAID')
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to))
            ->when($payout, fn ($q) => $q->where('payout_status', $payout));

        $count = $base()->count();

        return [
            'generated_at' => now(),
            'from' => $from, 'to' => $to, 'payout' => $payout,
            'count' => $count,
            'truncated' => $count > self::MAX_ROWS,
            'received' => (int) $base()->sum('amount'),
            'pending_settlement' => (int) $base()->where('payout_status', 'PENDING_SETTLEMENT')->sum('amount'),
            'settled' => (int) $base()->where('payout_status', 'SETTLED')->sum('amount'),
            'by_method' => $base()->selectRaw('payment_type, count(*) as c, sum(amount) as total')->groupBy('payment_type')->orderByDesc('total')->get(),
            'rows' => $base()->with('shipment.customer')->orderByDesc('paid_at')->limit(self::MAX_ROWS)->get(),
        ];
    }
}
