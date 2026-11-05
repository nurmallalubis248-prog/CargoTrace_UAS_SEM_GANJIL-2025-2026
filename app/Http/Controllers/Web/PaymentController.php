<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    /** Freight fee = weight x rate (config), minimum fee applies. IDR, integer. */
    public static function amountFor(Shipment $s): int
    {
        return (int) max(config('cargotrace.payment.min_fee'), round($s->weight_kg * config('cargotrace.payment.rate_per_kg')));
    }

    public function store(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('view', $shipment);

        if ($shipment->payments()->where('status', 'PAID')->exists()) {
            return back()->with('error', 'This shipment is already paid.');
        }

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

            return back()->with('error', 'Payment could not be started: '.$e->getMessage());
        }

        $payment->update(['snap_token' => $snap['token'], 'redirect_url' => $snap['redirect_url'] ?? null]);

        return redirect()->away($payment->redirect_url);
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
}
