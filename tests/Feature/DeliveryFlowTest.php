<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use App\Services\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

/** ARRIVED -> "Confirm received" -> DELIVERED, with notifications and the live map. */
class DeliveryFlowTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cargotrace.simulation.enabled' => false]);
    }

    private function arrived(User $customer, User $operator): Shipment
    {
        $s = $this->makeShipment($customer, $operator);
        $s->forceFill(['current_status' => 'ARRIVED', 'actual_arrival_at' => now()->subHours(5)])->save();

        return $s->fresh();
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
            'message' => "Shipment {$shipment->shipment_number} has arrived. Please confirm receipt.",
        ]);

        Sanctum::actingAs($admin);
        $position = collect($this->getJson('/api/dashboard')->assertOk()->json('positions'))->firstWhere('id', $shipment->id);
        $this->assertNotNull($position, 'an ARRIVED shipment must stay on the live map');
        $this->assertSame('ARRIVED', $position['status']);
    }

    public function test_customer_confirms_receipt_and_the_cargo_stays_on_the_map(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->arrived($customer, $operator);
        $arrival = $shipment->actual_arrival_at->getTimestamp();

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

        $this->actingAs($this->makeUser(User::ROLE_CUSTOMER))->post("/shipments/{$shipment->id}/confirm")->assertForbidden();
        $this->actingAs($operator)->post("/shipments/{$shipment->id}/confirm")->assertForbidden();

        Sanctum::actingAs($operator);
        $this->postJson("/api/shipments/{$shipment->id}/tracking", ['event_type' => 'STATUS_UPDATE', 'status' => 'DELIVERED'])->assertStatus(422);
        $this->assertSame('ARRIVED', $shipment->fresh()->current_status->value);
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