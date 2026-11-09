<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Services\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

/** ARRIVED -> PAID -> "Confirm received" -> DELIVERED, with notifications, receipt PDF and the live map. */
class DeliveryFlowTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private const SERVER_KEY = 'SB-Mid-server-test-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.midtrans.server_key' => self::SERVER_KEY, 'services.erp.webhook_url' => null]);
    }

    private function arrived(User $customer, User $operator): Shipment
    {
        $s = $this->makeShipment($customer, $operator);
        $s->forceFill(['current_status' => 'ARRIVED', 'actual_arrival_at' => now()->subHours(5)])->save();

        return $s->fresh();
    }

    private function payment(Shipment $s, User $customer, string $status = 'PAID'): Payment
    {
        return $s->payments()->create(['user_id' => $customer->id, 'order_id' => 'CT-FLOW-'.uniqid(), 'amount' => 100000, 'status' => $status, 'payment_type' => 'qris']);
    }

    public function test_arrival_records_the_arrival_time_notifies_the_customer_and_keeps_the_cargo_on_the_map(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator, [
            'expected_departure_at' => now()->subDays(3)->format('Y-m-d H:i:s'),
            'expected_arrival_at' => now()->subHours(10)->format('Y-m-d H:i:s'),
        ], now()->subDays(4));

        foreach (['BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'ARRIVED'] as $status) {
            app(TrackingService::class)->addEvent($shipment, ['event_type' => 'STATUS_UPDATE', 'status' => $status, 'location_id' => $shipment->destination_id], $operator);
        }
        $shipment->refresh();

        $this->assertSame('ARRIVED', $shipment->current_status->value);
        $this->assertNotNull($shipment->actual_arrival_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id, 'shipment_id' => $shipment->id, 'type' => 'STATUS_CHANGED',
            'message' => "Shipment {$shipment->shipment_number} has arrived. Please pay the freight, then confirm receipt.",
        ]);

        Sanctum::actingAs($admin);
        $position = collect($this->getJson('/api/dashboard')->assertOk()->json('positions'))->firstWhere('id', $shipment->id);
        $this->assertNotNull($position, 'an ARRIVED shipment must stay on the live map');
        $this->assertSame('ARRIVED', $position['status']);
    }

    public function test_customer_cannot_confirm_receipt_before_paying(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->arrived($customer, $operator);
        $this->payment($shipment, $customer, 'PENDING');

        $this->actingAs($customer)->from("/shipments/{$shipment->id}")
            ->post("/shipments/{$shipment->id}/confirm")
            ->assertRedirect("/shipments/{$shipment->id}")
            ->assertSessionHas('error');

        $this->assertSame('ARRIVED', $shipment->fresh()->current_status->value);
    }

    public function test_customer_confirms_after_paying_and_the_cargo_stays_on_the_map(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->arrived($customer, $operator);
        $arrival = $shipment->actual_arrival_at->getTimestamp();
        $this->payment($shipment, $customer);

        $this->actingAs($customer)->post("/shipments/{$shipment->id}/confirm")
            ->assertRedirect(route('shipments.show', $shipment))
            ->assertSessionHas('success');

        $shipment->refresh();
        $this->assertSame('DELIVERED', $shipment->current_status->value);
        $this->assertSame($arrival, $shipment->actual_arrival_at->getTimestamp(), 'the arrival time must not be overwritten by the confirmation time');
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'shipment_id' => $shipment->id, 'type' => 'DELIVERY_CONFIRMED']);

        Sanctum::actingAs($admin);
        $ids = collect($this->getJson('/api/dashboard')->assertOk()->json('positions'))->pluck('id');
        $this->assertTrue($ids->contains($shipment->id), 'a confirmed delivery stays visible on the map');
    }

    public function test_only_the_owner_can_confirm_and_operators_cannot_set_delivered(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->arrived($customer, $operator);
        $this->payment($shipment, $customer);

        $this->actingAs($this->makeUser(User::ROLE_CUSTOMER))->post("/shipments/{$shipment->id}/confirm")->assertForbidden();
        $this->actingAs($operator)->post("/shipments/{$shipment->id}/confirm")->assertForbidden();

        Sanctum::actingAs($operator);
        $this->postJson("/api/shipments/{$shipment->id}/tracking", ['event_type' => 'STATUS_UPDATE', 'status' => 'DELIVERED'])->assertStatus(422);
        $this->assertSame('ARRIVED', $shipment->fresh()->current_status->value);
    }

    public function test_a_paid_webhook_notifies_admins_and_customer_and_starts_as_pending_settlement(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->arrived($customer, $operator);
        $payment = $this->payment($shipment, $customer, 'PENDING');

        $n = ['order_id' => $payment->order_id, 'status_code' => '200', 'gross_amount' => '100000.00', 'transaction_status' => 'settlement', 'payment_type' => 'qris', 'fraud_status' => 'accept'];
        $n['signature_key'] = hash('sha512', $n['order_id'].$n['status_code'].$n['gross_amount'].self::SERVER_KEY);
        $this->postJson('/midtrans/notification', $n)->assertOk();

        $payment->refresh();
        $this->assertSame('PAID', $payment->status);
        $this->assertSame('PENDING_SETTLEMENT', $payment->payout_status);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'shipment_id' => $shipment->id, 'type' => 'PAYMENT_RECEIVED']);
        $this->assertDatabaseHas('notifications', ['user_id' => $customer->id, 'shipment_id' => $shipment->id, 'type' => 'PAYMENT_CONFIRMED']);
    }

    public function test_receipt_pdf_rules(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $paidShipment = $this->arrived($customer, $operator);
        $paid = $this->payment($paidShipment, $customer);
        $pending = $this->payment($this->arrived($customer, $operator), $customer, 'PENDING');

        $this->actingAs($this->makeUser(User::ROLE_CUSTOMER))->get("/payments/{$paid->id}/receipt")->assertForbidden();
        $this->actingAs($customer)->get("/payments/{$pending->id}/receipt")->assertNotFound();

        $response = $this->actingAs($customer)->get("/payments/{$paid->id}/receipt");
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $response->assertSessionHas('error');

            return;
        }
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_receipt_view_follows_the_selected_language(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $payment = $this->payment($this->arrived($customer, $operator), $customer)->load('shipment.customer', 'shipment.origin', 'shipment.destination', 'shipment.carrier');

        app()->setLocale('id');
        $id = view('payments.receipt', ['payment' => $payment])->render();
        app()->setLocale('en');
        $en = view('payments.receipt', ['payment' => $payment])->render();

        $this->assertStringContainsString('Resi pembayaran', $id);
        $this->assertStringContainsString('LUNAS', $id);
        $this->assertStringContainsString('Payment receipt', $en);
        $this->assertStringContainsString('RCP-'.$payment->order_id, $en);
    }

    public function test_unread_counter_endpoint_feeds_the_live_badge(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->arrived($customer, $operator);
        app(\App\Services\NotificationService::class)->notifyUser($customer->id, $shipment, 'STATUS_CHANGED', 'Test');

        $unread = $this->actingAs($customer)->getJson('/notifications/unread')->assertOk()->json('unread');
        $this->assertGreaterThanOrEqual(1, $unread);
    }
}