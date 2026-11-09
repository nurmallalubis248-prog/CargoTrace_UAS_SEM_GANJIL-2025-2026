@extends('layouts.app')
@section('title', 'Accounting')
@section('content')
@php $idr = fn ($v) => 'Rp '.number_format($v, 0, ',', '.'); @endphp
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-2xl font-bold">Accounting</h1>
    <a class="btn-light" href="{{ route('admin.accounting.export') }}">Export CSV</a>
</div>

<form method="GET" action="{{ route('admin.accounting.pdf') }}" id="fin-pdf" class="card mb-4 flex flex-wrap items-end gap-3">
    <div><label class="label">Paid from</label><input class="input" type="date" name="date_from"></div>
    <div><label class="label">Paid to</label><input class="input" type="date" name="date_to"></div>
    <div>
        <label class="label">Payout status</label>
        <select class="input" name="payout">
            <option value="">All</option>
            <option value="PENDING_SETTLEMENT">Pending settlement</option>
            <option value="SETTLED">Settled</option>
        </select>
    </div>
    <input type="hidden" name="lang" value="">
    <button class="btn-accent" type="submit">Download PDF report</button>
</form>

<div class="grid grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] gap-4">
    <div class="card"><p class="text-xs font-semibold text-ink-500">Revenue received</p><p class="mt-1 text-xl font-bold" data-idr="{{ $revenue }}">{{ $idr($revenue) }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Pending settlement</p><p class="mt-1 text-xl font-bold" data-idr="{{ $pendingSettlement }}">{{ $idr($pendingSettlement) }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Settled</p><p class="mt-1 text-xl font-bold" data-idr="{{ $settled }}">{{ $idr($settled) }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Pending payments</p><p class="mt-1 text-xl font-bold" data-idr="{{ $pending }}">{{ $idr($pending) }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Failed / expired</p><p class="mt-1 text-xl font-bold">{{ $failed }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Transactions</p><p class="mt-1 text-xl font-bold">{{ $count }}</p></div>
</div>
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="card">
        <h2 class="mb-2 font-semibold">Monthly revenue</h2>
        <table class="min-w-full text-sm"><thead><tr><th class="th">Month</th><th class="th">Revenue</th></tr></thead><tbody>
        @forelse($monthly as $m => $v)<tr><td class="td">{{ $m }}</td><td class="td" data-idr="{{ $v }}">{{ $idr($v) }}</td></tr>@empty<tr><td class="td" colspan="2">No transactions yet.</td></tr>@endforelse
        </tbody></table>
    </div>
    <div class="card overflow-x-auto p-0 lg:col-span-2">
        <h2 class="p-4 pb-0 font-semibold">Ledger</h2>
        <table class="min-w-full divide-y divide-ink-200">
            <thead class="bg-ink-50"><tr><th class="th">Order ID</th><th class="th">Shipment</th><th class="th">Customer</th><th class="th">Method</th><th class="th">Amount</th><th class="th">Status</th><th class="th">Paid at</th><th class="th">Payout</th><th class="th">Actions</th></tr></thead>
            <tbody class="divide-y divide-ink-100">
            @forelse($payments as $p)
                <tr><td class="td text-xs">{{ $p->order_id }}</td><td class="td">{{ $p->shipment?->shipment_number }}</td><td class="td">{{ $p->shipment?->customer?->name }}</td>
                    <td class="td">{{ $p->payment_type ?? '-' }}</td><td class="td" data-idr="{{ $p->amount }}">{{ $idr($p->amount) }}</td>
                    <td class="td">{{ $p->status }}</td><td class="td">{{ $p->paid_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td class="td text-xs">@if($p->isPaid()){{ $p->payout_status === 'SETTLED' ? 'SETTLED' : 'PENDING' }}@else - @endif</td>
                    <td class="td">
                        @if($p->isPaid())
                            <a data-pdf-lang class="text-xs font-semibold text-matcha-700 hover:underline" href="{{ route('payments.receipt', $p) }}">Receipt</a>
                            @if($p->payout_status !== 'SETTLED')
                                <form class="mt-1" method="POST" action="{{ route('admin.accounting.settle', $p) }}">@csrf<button class="text-xs font-semibold text-sakura-600 hover:underline" type="submit">Mark settled</button></form>
                            @endif
                        @endif
                    </td></tr>
            @empty<tr><td class="td" colspan="9">No transactions yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // The UI language is chosen in the browser: tell the server which language the PDF should use.
    document.getElementById('fin-pdf').addEventListener('submit', function () {
        let l = null; try { l = localStorage.getItem('ct_lang'); } catch (e) { /* ignore */ }
        this.elements.lang.value = (l === 'id' || l === 'en') ? l : '';
    });
</script>
@endpush
