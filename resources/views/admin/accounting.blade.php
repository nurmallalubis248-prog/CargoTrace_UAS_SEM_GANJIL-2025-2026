@extends('layouts.app')
@section('title', 'Accounting')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-2xl font-bold">Accounting</h1>
    <a class="btn-light" href="{{ route('admin.accounting.export') }}">Export CSV</a>
</div>
<div class="grid grid-cols-2 gap-4 md:grid-cols-4">
    <div class="card"><p class="text-xs font-semibold text-ink-500">Revenue received</p><p class="mt-1 text-2xl font-bold" data-idr="{{ $revenue }}">Rp {{ number_format($revenue, 0, ',', '.') }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Pending payments</p><p class="mt-1 text-2xl font-bold" data-idr="{{ $pending }}">Rp {{ number_format($pending, 0, ',', '.') }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Failed / expired</p><p class="mt-1 text-2xl font-bold">{{ $failed }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Transactions</p><p class="mt-1 text-2xl font-bold">{{ $count }}</p></div>
</div>
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="card">
        <h2 class="mb-2 font-semibold">Monthly revenue</h2>
        <table class="min-w-full text-sm"><thead><tr><th class="th">Month</th><th class="th">Revenue</th></tr></thead><tbody>
        @forelse($monthly as $m => $v)<tr><td class="td">{{ $m }}</td><td class="td" data-idr="{{ $v }}">Rp {{ number_format($v, 0, ',', '.') }}</td></tr>@empty<tr><td class="td" colspan="2">No transactions yet.</td></tr>@endforelse
        </tbody></table>
    </div>
    <div class="card overflow-x-auto p-0 lg:col-span-2">
        <h2 class="p-4 pb-0 font-semibold">Ledger</h2>
        <table class="min-w-full divide-y divide-ink-200">
            <thead class="bg-ink-50"><tr><th class="th">Order ID</th><th class="th">Shipment</th><th class="th">Customer</th><th class="th">Method</th><th class="th">Amount</th><th class="th">Status</th><th class="th">Paid at</th></tr></thead>
            <tbody class="divide-y divide-ink-100">
            @forelse($payments as $p)
                <tr><td class="td text-xs">{{ $p->order_id }}</td><td class="td">{{ $p->shipment?->shipment_number }}</td><td class="td">{{ $p->shipment?->customer?->name }}</td>
                    <td class="td">{{ $p->payment_type ?? '-' }}</td><td class="td" data-idr="{{ $p->amount }}">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                    <td class="td">{{ $p->status }}</td><td class="td">{{ $p->paid_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
            @empty<tr><td class="td" colspan="7">No transactions yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
