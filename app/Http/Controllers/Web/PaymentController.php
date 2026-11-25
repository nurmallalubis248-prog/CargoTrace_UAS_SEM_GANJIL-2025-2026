<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\PdfLang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Freight fee and payment receipts. There is no online payment gateway: the fee is calculated from
 * the cargo weight, and a receipt (PDF) is available for payments that are recorded as PAID.
 */
class PaymentController extends Controller
{
    /** Freight fee = weight x rate (config), minimum fee applies. IDR, integer. */
    public static function amountFor(Shipment $s): int
    {
        return (int) max(config('cargotrace.payment.min_fee'), round($s->weight_kg * config('cargotrace.payment.rate_per_kg')));
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
