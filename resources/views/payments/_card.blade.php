@php
    $paid = $shipment->payments->firstWhere('status', 'PAID');
    $last = $shipment->payments->sortByDesc('id')->first();
    $amount = \App\Http\Controllers\Web\PaymentController::amountFor($shipment);
    $fmt = fn ($v) => 'Rp '.number_format($v, 0, ',', '.');
@endphp
<div class="card mt-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold">Freight payment</h2>
            <p class="text-sm text-ink-500">QRIS &middot; BCA / BNI / BRI / Mandiri / Permata / CIMB VA &middot; e-wallet &middot; card (via Midtrans)</p>
            <p class="mt-2 text-2xl font-extrabold text-matcha-900" data-idr="{{ $paid->amount ?? $amount }}">{{ $fmt($paid->amount ?? $amount) }}</p>
            <p class="text-[11px] text-ink-400">Charged in IDR; other currencies are shown for reference (static rates).</p>
            <p class="text-xs text-ink-500">{{ number_format($shipment->weight_kg, 0, ',', '.') }} kg &times; Rp {{ number_format(config('cargotrace.payment.rate_per_kg'), 0, ',', '.') }}/kg (min. {{ $fmt(config('cargotrace.payment.min_fee')) }})</p>
        </div>
        <div class="text-right">
            @if($paid)
                <span class="rounded-full bg-matcha-100 px-3 py-1 text-sm font-bold text-matcha-800">PAID &middot; {{ $paid->payment_type }}</span>
                <p class="mt-1 text-xs text-ink-500">{{ $paid->paid_at?->format('Y-m-d H:i') }} UTC &middot; {{ $paid->order_id }}</p>
            @else
                @if($last)
                    <p class="mb-2 text-xs text-ink-500">Last attempt: <b>{{ $last->status }}</b>
                        @if($last->status === 'PENDING')
                            &middot; <a class="text-sakura-600 underline" href="{{ route('payments.sync', $last) }}">refresh status</a>
                            @if($last->redirect_url)&middot; <a class="text-sakura-600 underline" href="{{ $last->redirect_url }}">continue paying</a>@endif
                        @endif
                    </p>
                @endif
                <form method="POST" action="{{ route('shipments.pay', $shipment) }}">@csrf
                    <button class="grad-brand rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow hover:opacity-90">Pay now</button>
                </form>
            @endif
        </div>
    </div>
</div>
