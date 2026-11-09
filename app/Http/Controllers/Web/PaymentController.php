<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Services\MidtransService;
use App\Support\PdfLang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    /** Freight fee = weight x rate (config), minimum fee applies. IDR, integer. */
    public static function amountFor(Shipment $s): int
    {
        return (int) max(config('cargotrace.payment.min_fee'), round($s->weight_kg * config('cargotrace.payment.rate_per_kg')));
    }

    /**
     * Starts (or resumes) a payment and returns the Snap token as JSON.
     * The browser opens the Midtrans popup (snap.pay) INSIDE the app, so the customer never leaves CargoTrace.
     */
    public function store(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        if ($shipment->payments()->where('status', 'PAID')->exists()) {
            return response()->json(['message' => 'This shipment is already paid.'], 422);
        }

        if (! $shipment->current_status->isPayable()) {
            return response()->json(['message' => 'This shipment can no longer be paid ('.$shipment->current_status->value.').'], 422);
        }

        // Refresh still-open attempts first (prevents double payment).
        foreach ($shipment->payments()->where('status', 'PENDING')->latest('id')->get() as $open) {
            $remote = $this->midtrans->fetchStatus($open);
            if ($remote) {
                $this->midtrans->apply($open, $remote);
            }
        }
        if ($shipment->payments()->where('status', 'PAID')->exists()) {
            return response()->json(['paid' => true]);
        }

        // Reuse a still-valid Snap token instead of creating a new order on every click.
        $reusable = $shipment->payments()->where('status', 'PENDING')->whereNotNull('snap_token')
            ->where('created_at', '>', now()->subHours(23))->latest('id')->first();
        if ($reusable) {
            return response()->json(['token' => $reusable->snap_token, 'payment_id' => $reusable->id]);
        }
        $shipment->payments()->where('status', 'PENDING')->update(['status' => 'EXPIRED']);

        $payment = $shipment->payments()->create([
            'user_id' => $request->user()->id,
            'order_id' => 'CT-'.$shipment->id.'-'.now()->format('ymdHis').'-'.random_int(100, 999),
            'amount' => self::amountFor($shipment),
            'status' => 'PENDING',
        ]);

        try {
            $snap = $this->midtrans->createSnap($payment, $request->user()->name, $request->user()->email);
        } catch (Throwable $e) {
            Log::warning('Midtrans snap failed: '.$e->getMessage());
            $payment->delete();

            return response()->json(['message' => 'Payment could not be started: '.$e->getMessage()], 422);
        }

        $payment->update(['snap_token' => $snap['token'], 'redirect_url' => $snap['redirect_url'] ?? null]);

        return response()->json(['token' => $payment->snap_token, 'payment_id' => $payment->id]);
    }

    /** Status check for the in-app popup (pulls the latest status from Midtrans, then returns it as JSON). */
    public function status(Payment $payment): JsonResponse
    {
        Gate::authorize('view', $payment->shipment);

        $remote = $this->midtrans->fetchStatus($payment);
        if ($remote) {
            $this->midtrans->apply($payment, $remote);
        }

        return response()->json(['status' => $payment->fresh()->status]);
    }

    /** Called when the customer returns from Snap (and by the "refresh" button). */
    public function sync(Payment $payment): RedirectResponse
    {
        Gate::authorize('view', $payment->shipment);

        $status = $this->midtrans->fetchStatus($payment);
        if ($status) {
            $this->midtrans->apply($payment, $status);
        }

        return redirect()->route('shipments.show', $payment->shipment)
            ->with($payment->fresh()->isPaid() ? 'success' : 'error', $payment->fresh()->isPaid() ? 'Payment received. Thank you!' : 'Payment status: '.$payment->fresh()->status);
    }

    /** Midtrans HTTP notification (webhook). Public, CSRF-exempt, protected by signature check. */
    public function notification(Request $request): JsonResponse
    {
        $n = $request->all();
        if (! $this->midtrans->validSignature($n)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $payment = Payment::where('order_id', $n['order_id'] ?? '')->first();
        if (! $payment) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $this->midtrans->apply($payment, $n);

        return response()->json(['message' => 'OK']);
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
