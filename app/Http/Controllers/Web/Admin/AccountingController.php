<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\FinanceReportService;
use App\Support\PdfLang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** Accounting: payment ledger, revenue, payout (settlement) status, CSV and PDF export (IDR). */
class AccountingController extends Controller
{
    public function index(): View
    {
        $payments = Payment::with('shipment.customer')->latest()->limit(300)->get();
        $paid = Payment::where('status', 'PAID')->get();

        return view('admin.accounting', [
            'payments' => $payments,
            'revenue' => (int) $paid->sum('amount'),
            'pendingSettlement' => (int) $paid->where('payout_status', 'PENDING_SETTLEMENT')->sum('amount'),
            'settled' => (int) $paid->where('payout_status', 'SETTLED')->sum('amount'),
            'pending' => (int) Payment::where('status', 'PENDING')->sum('amount'),
            'failed' => Payment::whereIn('status', ['FAILED', 'EXPIRED', 'CANCELLED'])->count(),
            'count' => Payment::count(),
            'monthly' => $paid->groupBy(fn ($p) => $p->paid_at?->format('Y-m'))->map(fn ($g) => (int) $g->sum('amount'))->sortKeysDesc(),
        ]);
    }

    /** The admin confirms that the money of a PAID payment reached the bank account (payout / pencairan). */
    public function settle(Request $request, Payment $payment, AuditService $audit): RedirectResponse
    {
        abort_unless($payment->isPaid(), 422);

        if ($payment->payout_status !== 'SETTLED') {
            $payment->forceFill(['payout_status' => 'SETTLED', 'settled_at' => now()])->save();
            $audit->log($request->user(), 'payment.settled', $payment, ['order_id' => $payment->order_id, 'amount' => $payment->amount]);
        }

        return back()->with('success', 'Payment marked as settled.');
    }

    public function pdf(Request $request, FinanceReportService $finance): SymfonyResponse
    {
        $f = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'payout' => ['nullable', Rule::in(['PENDING_SETTLEMENT', 'SETTLED'])],
            'lang' => ['nullable', Rule::in(['en', 'id'])],
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::class;
        if (! class_exists($pdf)) {
            return back()->with('error', 'The PDF engine is not installed yet. Run: composer require barryvdh/laravel-dompdf');
        }
        PdfLang::apply($request);

        return $pdf::loadView('admin.finance-pdf', [
            'report' => $finance->build($f['date_from'] ?? null, $f['date_to'] ?? null, $f['payout'] ?? null),
            'user' => $request->user(),
        ])->setPaper('a4', 'landscape')->download('cargotrace-finance-'.now()->format('Ymd-His').'.pdf');
    }

    public function export(): Response
    {
        $rows = [['order_id', 'shipment', 'customer', 'method', 'amount_idr', 'status', 'payout_status', 'paid_at_utc', 'settled_at_utc']];
        foreach (Payment::with('shipment.customer')->orderBy('id')->get() as $p) {
            $rows[] = [$p->order_id, $p->shipment?->shipment_number, $p->shipment?->customer?->name, $p->payment_type, $p->amount, $p->status,
                $p->status === 'PAID' ? $p->payout_status : '', $p->paid_at?->format('Y-m-d H:i:s'), $p->settled_at?->format('Y-m-d H:i:s')];
        }
        $csv = implode("\n", array_map(fn ($r) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $r)), $rows));

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="cargotrace-ledger.csv"']);
    }
}
