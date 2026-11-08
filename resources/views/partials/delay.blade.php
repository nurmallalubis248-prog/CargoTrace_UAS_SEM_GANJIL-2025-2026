@if($shipment->is_delayed)
    @include('partials.badge', [
        'class' => $shipment->delay_severity === 'HIGH' ? 'bg-red-100 text-red-800' : ($shipment->delay_severity === 'MEDIUM' ? 'bg-sakura-100 text-sakura-700' : 'bg-yellow-100 text-yellow-800'),
        'text' => 'Delayed +'.rtrim(rtrim(number_format($shipment->delay_hours, 1), '0'), '.').'h · '.$shipment->delay_severity,
    ])
    @php $delayReason = $shipment->delayReason(); @endphp
    <span class="mt-0.5 block text-xs text-ink-500" @if($delayReason && $delayReason['description']) title="{{ $delayReason['description'] }}" @endif>{{ $delayReason ? $delayReason['label'] : 'Reason not recorded yet' }}</span>
@elseif($shipment->current_status->value === 'CANCELLED')
    <span class="text-xs text-ink-400">&mdash;</span>
@else
    @include('partials.badge', ['class' => 'bg-matcha-100 text-matcha-800', 'text' => 'On time'])
@endif
