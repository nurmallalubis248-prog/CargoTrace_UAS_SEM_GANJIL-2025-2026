<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\PdfLang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembayaran online (Midtrans) sudah dihapus. Yang tersisa:
 *  - amountFor(): tarif kargo (dipakai tempat lain)
 *  - receipt(): struk PDF untuk pembayaran lama yang sudah PAID
 *  - store/status/sync: pengait kosong supaya tombol/route lama di tampilan tidak membuat error 500.
 */
class PaymentController extends Controller
{
    /** Freight fee = weight x rate (config), minimum fee applies. IDR, integer. */
    public static function amountFor(Shipment $s): int
    {
        return (int) max(config('cargotrace.payment.min_fee'), round($s->weight_kg * config('cargotrace.payment.rate_per_kg')));
    }

    public function store(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        return response()->json(['message' => 'Pembayaran online sudah dinonaktifkan.'], 410);
    }

    public function status(Payment $payment): JsonResponse
    {
        Gate::authorize('view', $payment->shipment);

        return response()->json(['status' => $payment->status]);
    }

    public function sync(Payment $payment): RedirectResponse
    {
        Gate::authorize('view', $payment->shipment);

        return redirect()->route('shipments.show', $payment->shipment);
    }

    /** Payment receipt (PDF). Only for PAID payments, only for people who may view the shipment. */
    public function receipt(Request $request, Payment $payment): Response
    {
        Gate::authorize('view', $payment->shipment);
        abort_unless($payment->isPaid(), 404);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::class;
        if (! class_exists($pdf)) {
            return back()->with('error', 'The PDF engine is not installed yet. Run: composer require barryvdh/laravel-dompdf');
        }
        PdfLang::apply($request);

        return $pdf::loadView('payments.receipt', [
            'payment' => $payment->loadMissing('shipment.customer', 'shipment.origin', 'shipment.destination', 'shipment.carrier'),
        ])->setPaper('a4', 'portrait')->download('receipt-'.$payment->order_id.'.pdf');
    }
}
