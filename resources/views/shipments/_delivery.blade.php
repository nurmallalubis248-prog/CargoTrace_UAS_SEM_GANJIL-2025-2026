@php
    // Delivery card: shows the delivery status and, in manual mode, the "Confirm received" button for the customer.
    $isOwner = (int) auth()->id() === (int) $shipment->customer_id;
    $arrived = $shipment->current_status->value === 'ARRIVED';
    $delivered = $shipment->current_status->value === 'DELIVERED';
    $deliveredAt = $delivered ? $shipment->trackingEvents()->where('status', 'DELIVERED')->orderByDesc('occurred_at')->first()?->occurred_at : null;
@endphp
<div class="card mt-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold">Delivery</h2>
            <p class="mt-1 text-xs text-ink-500">Cargo: {{ number_format($shipment->weight_kg, 0, ',', '.') }} kg</p>
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
        </div>
    </div>
</div>
