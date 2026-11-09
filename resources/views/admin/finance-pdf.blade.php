<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ __('ui.finance.title') }}</title>
<style>
    @page { margin: 28px 30px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #26331A; }
    h1 { font-size: 18px; margin: 0 0 2px; color: #3F5729; }
    h2 { font-size: 11px; margin: 14px 0 4px; }
    .muted { color: #737B69; }
    .kpis { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 10px -6px 4px; }
    .kpis td { border: 1px solid #C5CBBB; border-radius: 6px; padding: 6px 8px; width: 25%; }
    .kpis .v { font-size: 13px; font-weight: bold; }
    table.list { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.list th { background: #E9EEE0; text-align: left; padding: 5px 6px; border-bottom: 1px solid #A9B497; font-size: 8.5px; }
    table.list td { padding: 4px 6px; border-bottom: 1px solid #E2E6DA; vertical-align: top; }
    .r { text-align: right; }
    .foot { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; font-size: 8px; color: #737B69; }
</style>
</head>
<body>
@php $idr = fn ($v) => 'Rp '.number_format($v, 0, ',', '.'); @endphp
<h1>CargoTrace · {{ __('ui.finance.title') }}</h1>
<div class="muted">
    {{ __('ui.finance.generated') }} {{ $report['generated_at']->format('Y-m-d H:i') }} UTC {{ __('ui.reports.by') }} {{ $user->name }}
    · {{ __('ui.finance.period') }}: {{ $report['from'] ?: __('ui.finance.all') }} – {{ $report['to'] ?: __('ui.finance.all') }}
    @if($report['payout']) · {{ __('ui.finance.payout') }}: {{ $report['payout'] === 'SETTLED' ? __('ui.finance.settled') : __('ui.finance.pending') }} @endif
</div>

<table class="kpis"><tr>
    <td><div class="muted">{{ __('ui.finance.received') }}</div><div class="v">{{ $idr($report['received']) }}</div></td>
    <td><div class="muted">{{ __('ui.finance.pending') }}</div><div class="v">{{ $idr($report['pending_settlement']) }}</div></td>
    <td><div class="muted">{{ __('ui.finance.settled') }}</div><div class="v">{{ $idr($report['settled']) }}</div></td>
    <td><div class="muted">{{ __('ui.finance.count') }}</div><div class="v">{{ $report['count'] }}</div></td>
</tr></table>

<h2>{{ __('ui.finance.by_method') }}</h2>
<table class="list">
    <thead><tr><th>{{ __('ui.finance.method') }}</th><th class="r">{{ __('ui.finance.count') }}</th><th class="r">{{ __('ui.finance.amount') }}</th></tr></thead>
    <tbody>
    @forelse($report['by_method'] as $m)
        <tr><td>{{ strtoupper((string) $m->payment_type) ?: '-' }}</td><td class="r">{{ $m->c }}</td><td class="r">{{ $idr($m->total) }}</td></tr>
    @empty
        <tr><td colspan="3" class="muted">{{ __('ui.finance.empty') }}</td></tr>
    @endforelse
    </tbody>
</table>

<h2>{{ __('ui.finance.title') }}</h2>
<table class="list">
    <thead><tr>
        <th>{{ __('ui.finance.order') }}</th><th>{{ __('ui.finance.shipment') }}</th><th>{{ __('ui.finance.customer') }}</th><th>{{ __('ui.finance.method') }}</th>
        <th class="r">{{ __('ui.finance.amount') }}</th><th>{{ __('ui.finance.paid_at') }}</th><th>{{ __('ui.finance.payout') }}</th>
    </tr></thead>
    <tbody>
    @forelse($report['rows'] as $p)
        <tr>
            <td>{{ $p->order_id }}</td><td>{{ $p->shipment?->shipment_number }}</td><td>{{ $p->shipment?->customer?->name }}</td>
            <td>{{ strtoupper((string) $p->payment_type) ?: '-' }}</td><td class="r">{{ $idr($p->amount) }}</td>
            <td>{{ $p->paid_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $p->payout_status === 'SETTLED' ? __('ui.finance.settled') : __('ui.finance.pending') }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted">{{ __('ui.finance.empty') }}</td></tr>
    @endforelse
    </tbody>
</table>
@if($report['truncated'])<p class="muted">{{ __('ui.finance.truncated', ['n' => \App\Services\FinanceReportService::MAX_ROWS]) }}</p>@endif
<div class="foot">CargoTrace · {{ $report['generated_at']->format('Y-m-d H:i') }} UTC</div>
</body>
</html>
