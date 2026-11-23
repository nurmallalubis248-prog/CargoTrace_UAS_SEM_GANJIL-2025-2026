@php
    // Pembayaran online (Midtrans) sudah dihapus. Kartu ini hanya menampilkan tarif kargo, status pengiriman,
    // tombol konfirmasi terima (mode manual), dan struk untuk pembayaran lama yang sudah PAID.
    $paid = $shipment->payments->firstWhere('status', 'PAID');
    $amount = \App\Http\Controllers\Web\PaymentController::amountFor($shipment);
    $fmt = fn ($v) => 'Rp '.number_format($v, 0, ',', '.');
    $isOwner = (int) auth()->id() === (int) $shipment->customer_id;
    $arrived = $shipment->current_status->value === 'ARRIVED';
    $delivered = $shipment->current_status->value === 'DELIVERED';
    $deliveredAt = $delivered ? $shipment->trackingEvents()->where('status', 'DELIVERED')->orderByDesc('occurred_at')->first()?->occurred_at : null;
@endphp
<div class="card mt-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold">Freight fee</h2>
            <p class="mt-2 text-2xl font-extrabold text-matcha-900" data-idr="{{ $paid->amount ?? $amount }}">{{ $fmt($paid->amount ?? $amount) }}</p>
            <p class="text-[11px] text-ink-400">Shown in IDR; other currencies are for reference (static rates).</p>
            <p class="text-xs text-ink-500">{{ number_format($shipment->weight_kg, 0, ',', '.') }} kg &times; Rp {{ number_format(config('cargotrace.payment.rate_per_kg'), 0, ',', '.') }}/kg (min. {{ $fmt(config('cargotrace.payment.min_fee')) }})</p>
        </div>
        <div class="text-right">
            @if($delivered)
                <span class="rounded-full bg-matcha-600 px-3 py-1 text-sm font-bold text-white">DELIVERED</span>
                @if($deliveredAt)<p class="mt-2 text-xs text-matcha-800">{{ $deliveredAt->format('Y-m-d H:i') }} UTC</p>@endif
            @elseif($arrived && $isOwner)
                <form method="POST" action="{{ route('shipments.confirm', $shipment) }}">@csrf
                    <button class="btn" type="submit">Confirm received</button>
                </form>
            @elseif($arrived)
                <p class="text-xs text-ink-500">Waiting for the customer to confirm receipt.</p>
            @else
                <p class="text-sm text-ink-500">{{ $shipment->current_status->value === 'CANCELLED' ? 'This shipment was cancelled.' : 'On its way. Delivery is recorded automatically on arrival.' }}</p>
            @endif
            @if($paid)
                <div class="mt-3">
                    <a data-pdf-lang class="btn-light" href="{{ route('payments.receipt', $paid) }}">Download receipt (PDF)</a>
                </div>
            @endif
        </div>
    </div>
</div>
