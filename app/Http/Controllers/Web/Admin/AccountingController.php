<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Lightweight accounting: payment ledger, revenue totals, monthly revenue, CSV export (IDR). */
class AccountingController extends Controller
{
    public function index(): View
    {
        $payments = Payment::with('shipment.customer')->latest()->limit(300)->get();
        $paid = Payment::where('status', 'PAID')->get();

        return view('admin.accounting', [
            'payments' => $payments,
            'revenue' => (int) $paid->sum('amount'),
            'pending' => (int) Payment::where('status', 'PENDING')->sum('amount'),
            'failed' => Payment::whereIn('status', ['FAILED', 'EXPIRED', 'CANCELLED'])->count(),
            'count' => Payment::count(),
            'monthly' => $paid->groupBy(fn ($p) => $p->paid_at?->format('Y-m'))->map(fn ($g) => (int) $g->sum('amount'))->sortKeysDesc(),
        ]);
    }

    public function export(): Response
    {
        $rows = [['order_id', 'shipment', 'customer', 'method', 'amount_idr', 'status', 'paid_at_utc']];
        foreach (Payment::with('shipment.customer')->orderBy('id')->get() as $p) {
            $rows[] = [$p->order_id, $p->shipment?->shipment_number, $p->shipment?->customer?->name, $p->payment_type, $p->amount, $p->status, $p->paid_at?->format('Y-m-d H:i:s')];
        }
        $csv = implode("\n", array_map(fn ($r) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $r)), $rows));

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="cargotrace-ledger.csv"']);
    }
}
