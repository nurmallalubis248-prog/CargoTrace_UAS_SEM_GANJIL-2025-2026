<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Real Midtrans integration (Snap + Status API) through the Laravel HTTP Client.
 * Sandbox keys => real API calls, test money. Production keys => real money.
 * Keys live in .env only (MIDTRANS_SERVER_KEY, MIDTRANS_IS_PRODUCTION).
 */
class MidtransService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    private function cfg(string $k)
    {
        return config('services.midtrans.'.$k);
    }

    private function snapUrl(): string
    {
        return $this->cfg('is_production') ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function apiUrl(): string
    {
        return $this->cfg('is_production') ? 'https://api.midtrans.com/v2' : 'https://api.sandbox.midtrans.com/v2';
    }

    public function configured(): bool
    {
        return filled($this->cfg('server_key'));
    }

    /** Creates a Snap transaction; Snap shows QRIS, GoPay, BCA/BNI/BRI/Permata/CIMB VA, Mandiri bill, cards, etc. */
    public function createSnap(Payment $payment, string $name, string $email): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY is not set in .env.');
        }

        // Safety: sandbox keys start with "SB-". Refuse a mismatch so real money is never charged by accident.
        $isSandboxKey = str_starts_with((string) $this->cfg('server_key'), 'SB-');
        if ($this->cfg('is_production') && $isSandboxKey) {
            throw new RuntimeException('MIDTRANS_IS_PRODUCTION=true but the server key is a SANDBOX key (starts with SB-).');
        }
        if (! $this->cfg('is_production') && ! $isSandboxKey) {
            throw new RuntimeException('MIDTRANS_IS_PRODUCTION=false but the server key is a PRODUCTION key (no SB- prefix).');
        }

        $res = Http::withBasicAuth($this->cfg('server_key'), '')->acceptJson()->timeout(15)
            ->post($this->snapUrl(), [
                'transaction_details' => ['order_id' => $payment->order_id, 'gross_amount' => $payment->amount],
                'customer_details' => ['first_name' => $name, 'email' => $email],
                'item_details' => [['id' => 'SHIP-'.$payment->shipment_id, 'price' => $payment->amount, 'quantity' => 1,
                    'name' => 'Freight '.$payment->shipment->shipment_number]],
                'callbacks' => ['finish' => route('payments.sync', $payment)],
            ]);

        if (! $res->successful() || ! $res->json('token')) {
            throw new RuntimeException('Midtrans error: '.($res->json('error_messages.0') ?? 'HTTP '.$res->status()));
        }

        return $res->json();
    }

    /** Pull the latest status (works on localhost where webhooks cannot reach us). */
    public function fetchStatus(Payment $payment): ?array
    {
        $res = Http::withBasicAuth($this->cfg('server_key'), '')->acceptJson()->timeout(15)
            ->get($this->apiUrl().'/'.$payment->order_id.'/status');

        if (! $res->successful()) {
            Log::warning('Midtrans status check failed ('.$res->status().') for '.$payment->order_id.': '.$res->body());
        }

        return $res->successful() ? $res->json() : null;
    }

    public function validSignature(array $n): bool
    {
        $expected = hash('sha512', ($n['order_id'] ?? '').($n['status_code'] ?? '').($n['gross_amount'] ?? '').$this->cfg('server_key'));

        return hash_equals($expected, (string) ($n['signature_key'] ?? ''));
    }

    /** Apply a Midtrans notification/status payload to the payment (idempotent). */
    public function apply(Payment $payment, array $n): Payment
    {
        $t = $n['transaction_status'] ?? '';
        $status = match (true) {
            in_array($t, ['settlement', 'capture'], true) && ($n['fraud_status'] ?? 'accept') === 'accept' => 'PAID',
            $t === 'expire' => 'EXPIRED',
            $t === 'cancel' => 'CANCELLED',
            in_array($t, ['deny', 'failure'], true) => 'FAILED',
            default => 'PENDING',
        };

        if ($payment->isPaid()) {
            return $payment; // never downgrade a paid payment
        }

        // A second settlement for the same shipment is a duplicate payment: keep it out of revenue, flag it for refund.
        if ($status === 'PAID' && Payment::where('shipment_id', $payment->shipment_id)
            ->where('status', 'PAID')->where('id', '!=', $payment->id)->exists()) {
            Log::warning('Duplicate settlement for shipment '.$payment->shipment_id.' (order '.$payment->order_id.'): refund it in the Midtrans dashboard.');
            $payment->forceFill(['status' => 'DUPLICATE', 'payment_type' => $n['payment_type'] ?? $payment->payment_type, 'raw_notification' => $n])->save();

            return $payment;
        }

        $payment->forceFill([
            'status' => $status,
            'payment_type' => $n['payment_type'] ?? $payment->payment_type,
            'raw_notification' => $n,
            'paid_at' => $status === 'PAID' ? now() : null,
        ])->save();

        if ($status === 'PAID') {
            $this->notifyPaid($payment);
            $this->notifyErp($payment);
        }

        return $payment;
    }

    /** Real-time (in-app) notice for the admins, and tell the customer that confirming receipt is now possible. */
    private function notifyPaid(Payment $payment): void
    {
        $shipment = $payment->shipment;
        if (! $shipment) {
            return;
        }
        $amount = 'Rp '.number_format($payment->amount, 0, ',', '.');
        $this->notifications->notifyAdmins('PAYMENT_RECEIVED', "Payment {$amount} received for shipment {$shipment->shipment_number} ({$payment->payment_type}).", $shipment);
        $tail = $shipment->current_status === \App\Enums\ShipmentStatus::ARRIVED ? ' You can now confirm receipt.' : ' You can confirm receipt once the cargo arrives.';
        $this->notifications->notifyUser($shipment->customer_id, $shipment, 'PAYMENT_CONFIRMED', "Payment received for {$shipment->shipment_number}.".$tail);
    }

    /** Push a "payment.paid" event to the external ERP (optional, ERP_WEBHOOK_URL). Never breaks the payment flow. */
    private function notifyErp(Payment $payment): void
    {
        $url = config('services.erp.webhook_url');
        if (! $url) {
            return;
        }
        try {
            Http::withHeaders(['X-ERP-KEY' => (string) config('services.erp.api_key')])->timeout(8)->post($url, [
                'event' => 'payment.paid', 'order_id' => $payment->order_id, 'amount_idr' => $payment->amount,
                'method' => $payment->payment_type, 'shipment' => $payment->shipment?->shipment_number, 'paid_at' => $payment->paid_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
