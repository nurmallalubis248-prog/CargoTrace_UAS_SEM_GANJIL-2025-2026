<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ __('ui.receipt.title') }} {{ $payment->order_id }}</title>
<style>
    @page { margin: 36px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #26331A; }
    h1 { font-size: 22px; margin: 0; color: #3F5729; }
    .muted { color: #737B69; }
    .badge { display: inline-block; background: #E4EDD8; color: #324422; font-weight: bold; padding: 3px 10px; border-radius: 10px; }
    table.info { width: 100%; border-collapse: collapse; margin-top: 18px; }
    table.info td { padding: 7px 8px; border-bottom: 1px solid #E2E6DA; vertical-align: top; }
    table.info td.k { width: 32%; color: #737B69; }
    .total { margin-top: 16px; text-align: right; font-size: 20px; font-weight: bold; color: #3F5729; }
    .foot { margin-top: 28px; font-size: 9px; color: #737B69; }
</style>
</head>
<body>
@php $s = $payment->shipment; @endphp
<table style="width:100%"><tr>
    <td><h1>CargoTrace</h1><div class="muted">{{ __('ui.receipt.title') }}</div></td>
    <td style="text-align:right"><div class="badge">{{ __('ui.receipt.paid') }}</div><div class="muted">{{ __('ui.receipt.number') }}: RCP-{{ $payment->order_id }}</div></td>
</tr></table>

<table class="info">
    <tr><td class="k">{{ __('ui.receipt.order') }}</td><td>{{ $payment->order_id }}</td></tr>
    <tr><td class="k">{{ __('ui.receipt.shipment') }}</td><td>{{ $s?->shipment_number }}</td></tr>
    <tr><td class="k">{{ __('ui.receipt.customer') }}</td><td>{{ $s?->customer?->name }}</td></tr>
    <tr><td class="k">{{ __('ui.receipt.route') }}</td><td>{{ $s?->origin?->name }} → {{ $s?->destination?->name }}</td></tr>
    <tr><td class="k">{{ __('ui.receipt.carrier') }}</td><td>{{ $s?->carrier?->name }}</td></tr>
    <tr><td class="k">{{ __('ui.receipt.weight') }}</td><td>{{ number_format((float) $s?->weight_kg, 0, ',', '.') }} kg</td></tr>
    <tr><td class="k">{{ __('ui.receipt.method') }}</td><td>{{ strtoupper((string) $payment->payment_type) ?: '-' }}</td></tr>
    <tr><td class="k">{{ __('ui.receipt.paid_at') }}</td><td>{{ $payment->paid_at?->format('Y-m-d H:i') }} UTC</td></tr>
    <tr><td class="k">{{ __('ui.receipt.status') }}</td><td>{{ __('ui.receipt.paid') }}</td></tr>
</table>

<div class="total">{{ __('ui.receipt.amount') }}: Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
<div class="foot">{{ __('ui.receipt.note') }}</div>
</body>
</html>
