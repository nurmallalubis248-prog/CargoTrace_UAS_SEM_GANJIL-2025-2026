{{-- Tabel pengiriman bergaya desain. Param: $rows (iterable pengiriman), $startNo (nomor urut pertama) --}}
@php
// Label & warna badge status (sesuai desain). Pengiriman terlambat yang masih aktif tampil sebagai "Delayed".
$statusView = function ($s) {
    $st = $s->current_status->value;
    if ($s->is_delayed && ! in_array($st, ['DELIVERED', 'CANCELLED'], true)) { return ['Delayed', 'red']; }
    return match ($st) {
        'CREATED', 'BOOKED' => ['Awaiting departure', 'orange'],
        'PICKED_UP' => ['In process', 'blue'],
        'IN_TRANSIT' => ['In transit', 'green'],
        'ARRIVED' => ['Arrived', 'sky'],
        'DELIVERED' => ['Completed', 'grey'],
        'CANCELLED' => ['Cancelled', 'rose'],
        default => [$st, 'grey'],
    };
};
@endphp
<div class="sh-tbl-wrap">
    <table class="sh-tbl">
        <thead>
        <tr>
            <th>No.</th>
            <th>Shipment no. / PO</th>
            <th>Route</th>
            <th>Carrier</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Expected arrival</th>
            <th>ETA</th>
            <th>Delay</th>
            <th style="text-align:center">Action</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $i => $s)
            @php [$stLabel, $stColor] = $statusView($s); $delayReason = $s->is_delayed ? $s->delayReason() : null; @endphp
            <tr>
                <td>{{ $startNo + $i }}</td>
                <td>
                    <a class="sh-num" href="{{ route('shipments.show', $s) }}" data-no-i18n>{{ $s->shipment_number }}</a>
                    @if($s->reference_number)<div class="sh-sub" data-no-i18n>{{ $s->reference_number }}</div>@endif
                </td>
                <td><span data-no-i18n>{{ $s->origin->name }} → {{ $s->destination->name }}</span></td>
                <td data-no-i18n>{{ $s->carrier->name }}</td>
                <td data-no-i18n>{{ $s->customer->name }}</td>
                <td><span class="sh-badge sh-b-{{ $stColor }}"><i></i><span>{{ $stLabel }}</span></span></td>
                <td class="sh-mono">{{ $s->expected_arrival_at->format('Y-m-d H:i') }}</td>
                <td class="sh-mono">{{ $s->estimated_arrival_at?->format('Y-m-d H:i') ?? '-' }}</td>
                <td>
                    @if($s->is_delayed && $s->current_status->value !== 'CANCELLED')
                        <span class="sh-late" @if($delayReason) title="{{ $delayReason['label'] }}" @endif>+{{ rtrim(rtrim(number_format($s->delay_hours, 1), '0'), '.') }}h</span>
                    @else
                        <span>-</span>
                    @endif
                </td>
                <td style="white-space:nowrap;text-align:center">
                    <a class="sh-detail" href="{{ route('shipments.show', $s) }}">
                        <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>Details</span>
                    </a>
                    @canany(['update', 'delete'], $s)
                        <details class="sh-kebab">
                            <summary aria-label="Action"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg></summary>
                            <div class="sh-menu" hidden>
                                @can('update', $s)<a href="{{ route('shipments.edit', $s) }}">Edit</a>@endcan
                                @can('delete', $s)
                                    <form method="POST" action="{{ route('shipments.destroy', $s) }}" data-confirm="Delete this shipment?">@csrf @method('DELETE')
                                        <button type="submit">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                </td>
            </tr>
        @empty
            <tr><td class="sh-empty" colspan="10">No shipments match your filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
