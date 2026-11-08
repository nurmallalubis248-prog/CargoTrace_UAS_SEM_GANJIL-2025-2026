<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function statusPayload(string $status, array $extra = []): array
    {
        return array_merge(['event_type' => 'STATUS_UPDATE', 'status' => $status], $extra);
    }

    public function test_valid_status_flow_updates_shipment_and_keeps_history(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        Sanctum::actingAs($operator);

        foreach (['BOOKED', 'PICKED_UP', 'IN_TRANSIT'] as $s) {
            $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload($s))->assertCreated();
        }

        $this->assertSame('IN_TRANSIT', $shipment->fresh()->current_status->value);
        $this->assertSame(4, $shipment->trackingEvents()->count()); // CREATED + 3 new, none overwritten
    }

    public function test_invalid_status_transition_is_rejected_with_422(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('DELIVERED'))
            ->assertStatus(422)
            ->assertJsonPath('errors.rule.0', fn ($m) => str_contains($m, 'Invalid status transition'));

        $this->assertSame('CREATED', $shipment->fresh()->current_status->value);
        $this->assertSame(1, $shipment->trackingEvents()->count());
    }

    public function test_delivered_shipment_is_terminal(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        Sanctum::actingAs($operator);

        foreach (['BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $s) {
            $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload($s))->assertCreated();
        }

        $this->assertNotNull($shipment->fresh()->actual_arrival_at);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('BOOKED'))->assertStatus(422);
        $this->postJson("/api/shipments/{$shipment->id}/tracking", ['event_type' => 'CHECKPOINT'])->assertStatus(422);
    }

    public function test_event_time_must_not_be_in_the_future_or_before_latest_event(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator, [], now()->subDays(2));
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('BOOKED', ['occurred_at' => now()->addDays(2)->format('Y-m-d H:i:s')]))
            ->assertStatus(422);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('BOOKED', ['occurred_at' => now()->subHour()->format('Y-m-d H:i:s')]))
            ->assertCreated();

        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('PICKED_UP', ['occurred_at' => now()->subHours(5)->format('Y-m-d H:i:s')]))
            ->assertStatus(422);
    }

    public function test_checkpoint_updates_current_location_and_validates_coordinates(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'event_type' => 'CHECKPOINT', 'latitude' => 120, 'longitude' => 10,
        ])->assertStatus(422)->assertJsonValidationErrors('latitude');

        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'event_type' => 'CHECKPOINT', 'latitude' => 6.95, 'longitude' => 79.85, 'location_label' => 'Off Colombo',
        ])->assertCreated();

        $fresh = $shipment->fresh();
        $this->assertSame('Off Colombo', $fresh->current_location_label);
        $this->assertEquals(6.95, $fresh->current_latitude);
    }

    public function test_timeline_is_chronological_and_marks_exceptions(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator, [], now()->subDay());
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('BOOKED', ['occurred_at' => now()->subHours(3)->format('Y-m-d H:i:s')]))->assertCreated();
        $this->postJson("/api/shipments/{$shipment->id}/exceptions", [
            'type' => 'CUSTOMS_HOLD', 'severity' => 'HIGH', 'description' => 'Held for inspection.',
        ])->assertCreated();

        $events = $this->getJson("/api/shipments/{$shipment->id}/tracking")->assertOk()->json('data');

        $times = array_column($events, 'occurred_at');
        $sorted = $times;
        sort($sorted);
        $this->assertSame($sorted, $times);
        $this->assertTrue(collect($events)->contains(fn ($e) => $e['is_exception'] === true && $e['event_type'] === 'EXCEPTION'));
    }

    public function test_exception_event_type_cannot_be_posted_directly_and_customers_cannot_add_events(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);

        Sanctum::actingAs($operator);
        $this->postJson("/api/shipments/{$shipment->id}/tracking", ['event_type' => 'EXCEPTION'])->assertStatus(422);

        Sanctum::actingAs($customer);
        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('BOOKED'))->assertForbidden();
        $this->getJson("/api/shipments/{$shipment->id}/tracking")->assertOk();
    }

    public function test_customer_is_notified_when_status_changes(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", $this->statusPayload('BOOKED'))->assertCreated();

        $this->assertDatabaseHas('notifications', ['user_id' => $customer->id, 'shipment_id' => $shipment->id, 'type' => 'STATUS_CHANGED']);
    }
}
