<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private const SERVER_KEY = 'SB-Mid-server-test-key';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.midtrans.server_key' => self::SERVER_KEY,
            'services.midtrans.is_production' => false,
            'services.erp.webhook_url' => null,
            'cargotrace.payment.rate_per_kg' => 5000,
            'cargotrace.payment.min_fee' => 50000,
        ]);
    }

    private function makePayment(Shipment $shipment, User $user, string $status = 'PENDING'): Payment
    {
        return $shipment->payments()->create([
            'user_id' => $user->id,
            'order_id' => 'CT-TEST-'.uniqid(),
            'amount' => 5000000,
            'status' => $status,
        ]);
    }

    private function signedNotification(Payment $payment, array $override = []): array
    {
        $n = array_merge([
            'order_id' => $payment->order_id,
            'status_code' => '200',
            'gross_amount' => '5000000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
        ], $override);
        $n['signature_key'] = hash('sha512', $n['order_id'].$n['status_code'].$n['gross_amount'].self::SERVER_KEY);

        return $n;
    }

    // ---- fee calculation ----------------------------------------------------

    public function test_fee_is_weight_times_rate_with_a_minimum(): void
    {
        $heavy = new Shipment();
        $heavy->weight_kg = 1000;
        $light = new Shipment();
        $light->weight_kg = 1;

        $this->assertSame(5000000, \App\Http\Controllers\Web\PaymentController::amountFor($heavy));
        $this->assertSame(50000, \App\Http\Controllers\Web\PaymentController::amountFor($light));
    }

    // ---- starting a payment -------------------------------------------------

    public function test_customer_can_start_payment_for_own_shipment(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'snap-token', 'redirect_url' => 'https://pay.test/abc'], 201)]);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator, ['weight_kg' => 1000]);

        $this->actingAs($customer)->post("/shipments/{$shipment->id}/pay")->assertRedirect('https://pay.test/abc');

        $this->assertDatabaseHas('payments', [
            'shipment_id' => $shipment->id, 'user_id' => $customer->id,
            'amount' => 5000000, 'status' => 'PENDING', 'snap_token' => 'snap-token',
        ]);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'sandbox.midtrans.com')
            && $r['transaction_details']['gross_amount'] === 5000000);
    }

    public function test_customer_cannot_pay_for_someone_elses_shipment(): void
    {
        Http::fake();
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $alice = $this->makeUser(User::ROLE_CUSTOMER);
        $bob = $this->makeUser(User::ROLE_CUSTOMER);
        $theirs = $this->makeShipment($bob, $operator);

        $status = $this->actingAs($alice)->post("/shipments/{$theirs->id}/pay")->getStatusCode();

        $this->assertContains($status, [403, 404]);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_guest_cannot_start_a_payment(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);

        $this->post("/shipments/{$shipment->id}/pay")->assertRedirect('/login');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_already_paid_shipment_cannot_be_paid_twice(): void
    {
        Http::fake();
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);
        $this->makePayment($shipment, $customer, 'PAID');

        $this->actingAs($customer)->post("/shipments/{$shipment->id}/pay")
            ->assertSessionHas('error', 'This shipment is already paid.');

        $this->assertDatabaseCount('payments', 1);
        Http::assertNothingSent();
    }

    public function test_provider_error_removes_the_pending_payment_and_shows_a_message(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['error_messages' => ['Access denied']], 401)]);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);

        $this->actingAs($customer)->post("/shipments/{$shipment->id}/pay")
            ->assertSessionHas('error');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_missing_server_key_fails_gracefully_without_calling_the_provider(): void
    {
        config(['services.midtrans.server_key' => null]);
        Http::fake();
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);

        $this->actingAs($customer)->post("/shipments/{$shipment->id}/pay")->assertSessionHas('error');

        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    // ---- webhook ------------------------------------------------------------

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $payment = $this->makePayment($this->makeShipment($customer, $operator), $customer);

        $n = $this->signedNotification($payment);
        $n['signature_key'] = 'forged';

        $this->postJson('/midtrans/notification', $n)->assertForbidden();
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_webhook_for_unknown_order_returns_404(): void
    {
        $n = [
            'order_id' => 'CT-DOES-NOT-EXIST', 'status_code' => '200', 'gross_amount' => '1.00',
            'transaction_status' => 'settlement',
        ];
        $n['signature_key'] = hash('sha512', $n['order_id'].$n['status_code'].$n['gross_amount'].self::SERVER_KEY);

        $this->postJson('/midtrans/notification', $n)->assertNotFound();
    }

    public function test_settlement_webhook_marks_payment_paid(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $payment = $this->makePayment($this->makeShipment($customer, $operator), $customer);

        $this->postJson('/midtrans/notification', $this->signedNotification($payment))->assertOk();

        $payment->refresh();
        $this->assertSame('PAID', $payment->status);
        $this->assertSame('qris', $payment->payment_type);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_expired_and_denied_notifications_map_to_the_right_status(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);

        $expired = $this->makePayment($this->makeShipment($customer, $operator), $customer);
        $this->postJson('/midtrans/notification', $this->signedNotification($expired, ['transaction_status' => 'expire', 'status_code' => '407']))->assertOk();
        $this->assertSame('EXPIRED', $expired->fresh()->status);

        $denied = $this->makePayment($this->makeShipment($customer, $operator), $customer);
        $this->postJson('/midtrans/notification', $this->signedNotification($denied, ['transaction_status' => 'deny', 'status_code' => '202']))->assertOk();
        $this->assertSame('FAILED', $denied->fresh()->status);
    }

    public function test_card_capture_flagged_by_fraud_check_is_not_marked_paid(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $payment = $this->makePayment($this->makeShipment($customer, $operator), $customer);

        $this->postJson('/midtrans/notification', $this->signedNotification($payment, [
            'transaction_status' => 'capture', 'fraud_status' => 'challenge',
        ]))->assertOk();

        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_a_paid_payment_is_never_downgraded_by_a_late_notification(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $payment = $this->makePayment($this->makeShipment($customer, $operator), $customer);

        $this->postJson('/midtrans/notification', $this->signedNotification($payment))->assertOk();
        $this->postJson('/midtrans/notification', $this->signedNotification($payment, ['transaction_status' => 'expire', 'status_code' => '407']))->assertOk();

        $this->assertSame('PAID', $payment->fresh()->status);
    }

    public function test_paid_notification_is_forwarded_to_erp_webhook_and_failures_do_not_break_the_flow(): void
    {
        config(['services.erp.webhook_url' => 'https://erp.test/hook', 'services.erp.api_key' => 'erp-test-key']);
        Http::fake(['erp.test/*' => Http::response('boom', 500)]);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $payment = $this->makePayment($this->makeShipment($customer, $operator), $customer);

        $this->postJson('/midtrans/notification', $this->signedNotification($payment))->assertOk();

        $this->assertSame('PAID', $payment->fresh()->status);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://erp.test/hook'
            && $r->hasHeader('X-ERP-KEY', 'erp-test-key')
            && $r['event'] === 'payment.paid'
            && $r['order_id'] === $payment->order_id);
    }
}
