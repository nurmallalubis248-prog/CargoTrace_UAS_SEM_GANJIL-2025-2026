<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ __('ui.reports.title') }}</title>
<style>
    @page { margin: 28px 30px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #26331A; }
    h1 { font-size: 18px; margin: 0 0 2px; color: #3F5729; }
    .muted { color: #737B69; }
    .kpis { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 10px -6px 8px; }
    .kpis td { border: 1px solid #C5CBBB; border-radius: 6px; padding: 6px 8px; width: 14%; }
    .kpis .v { font-size: 14px; font-weight: bold; }
    table.list { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.list th { background: #E9EEE0; text-align: left; padding: 5px 6px; border-bottom: 1px solid #A9B497; font-size: 8.5px; }
    table.list td { padding: 4px 6px; border-bottom: 1px solid #E2E6DA; vertical-align: top; }
    .late { color: #B91C1C; font-weight: bold; }
    .ok { color: #3F5729; }
    .foot { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; font-size: 8px; color: #737B69; }
</style>
</head>
<body>
@php $s = $report['summary']; $f = array_filter($filters, fn ($v) => $v !== null && $v !== ''); @endphp
<h1>CargoTrace · {{ __('ui.reports.title') }}</h1>
<div class="muted">
    {{ __('ui.reports.generated') }} {{ $report['generated_at']->format('Y-m-d H:i') }} UTC {{ __('ui.reports.by') }} {{ $user->name }}
    · {{ __('ui.reports.filters') }}:
    @if(count($f)) @foreach($f as $k => $v){{ $k }}={{ $v }}@if(! $loop->last), @endif @endforeach @else {{ __('ui.reports.none') }} @endif
</div>

<table class="kpis">
    <tr>
        <td><div class="muted">{{ __('ui.reports.total') }}</div><div class="v">{{ $s['total'] }}</div></td>
        <td><div class="muted">{{ __('ui.reports.in_transit') }}</div><div class="v">{{ $s['in_transit'] }}</div></td>
        <td><div class="muted">{{ __('ui.reports.delivered') }}</div><div class="v">{{ $s['delivered'] }}</div></td>
        <td><div class="muted">{{ __('ui.reports.delayed') }}</div><div class="v">{{ $s['delayed'] }}</div></td>
        <td><div class="muted">{{ __('ui.reports.open_exceptions') }}</div><div class="v">{{ $s['open_exceptions'] }}</div></td>
        <td><div class="muted">{{ __('ui.reports.delay_rate') }}</div><div class="v">{{ $s['delay_rate_pct'] }}%</div></td>
        <td><div class="muted">{{ __('ui.reports.revenue') }}</div><div class="v">Rp {{ number_format($s['revenue'], 0, ',', '.') }}</div></td>
    </tr>
</table>

<table class="list">
    <thead>
        <tr>
            <th>{{ __('ui.reports.number') }}</th><th>{{ __('ui.reports.route') }}</th><th>{{ __('ui.reports.carrier') }}</th>
            <th>{{ __('ui.reports.status') }}</th><th>{{ __('ui.reports.eta') }}</th><th>{{ __('ui.reports.delay') }}</th><th>{{ __('ui.reports.reason') }}</th>
        </tr>
    </thead>
    <tbody>
    @forelse($report['rows'] as $sh)
        @php $reason = $sh->delayReason(); @endphp
        <tr>
            <td>{{ $sh->shipment_number }}</td>
            <td>{{ $sh->origin?->name }} → {{ $sh->destination?->name }}</td>
            <td>{{ $sh->carrier?->name }}</td>
            <td>{{ str_replace('_', ' ', $sh->current_status->value) }}</td>
            <td>{{ $sh->expected_arrival_at?->format('Y-m-d') }}</td>
            <td>
                @if($sh->is_delayed)<span class="late">+{{ rtrim(rtrim(number_format($sh->delay_hours, 1), '0'), '.') }} h · {{ $sh->delay_severity }}</span>
                @else<span class="ok">{{ __('ui.reports.on_time') }}</span>@endif
            </td>
            <td>
                @if($sh->is_delayed)
                    @if($reason){{ $reason['type']->localized() }}@if($reason['description']) — {{ \Illuminate\Support\Str::limit($reason['description'], 80) }}@endif
                    @else<span class="muted">{{ __('ui.reports.no_reason') }}</span>@endif
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted">{{ __('ui.reports.empty') }}</td></tr>
    @endforelse
    </tbody>
</table>
@if($report['truncated'])<p class="muted">{{ __('ui.reports.truncated', ['n' => \App\Services\ReportService::MAX_ROWS]) }}</p>@endif
<div class="foot">CargoTrace · {{ $report['generated_at']->format('Y-m-d H:i') }} UTC</div>
</body>
</html>
