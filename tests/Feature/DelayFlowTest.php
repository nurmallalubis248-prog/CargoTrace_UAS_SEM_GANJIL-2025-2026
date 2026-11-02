<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class DelayFlowTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_eta_update_flags_delay_with_severity_and_notifies_customer(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $expected = now()->addDays(10)->startOfMinute();
        $shipment = $this->makeShipment($customer, $operator, [
            'expected_departure_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'expected_arrival_at' => $expected->format('Y-m-d H:i:s'),
        ]);
        Sanctum::actingAs($operator);

        // +10 h => MEDIUM (> 6 h, <= 24 h)
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'event_type' => 'ETA_UPDATE',
            'estimated_arrival_at' => $expected->copy()->addHours(10)->format('Y-m-d H:i:s'),
        ])->assertCreated()
            ->assertJsonPath('shipment.delay.is_delayed', true)
            ->assertJsonPath('shipment.delay.severity', 'MEDIUM');

        $this->assertDatabaseHas('notifications', ['user_id' => $customer->id, 'type' => 'SHIPMENT_DELAYED']);

        // +30 h => HIGH
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'event_type' => 'ETA_UPDATE',
            'estimated_arrival_at' => $expected->copy()->addHours(30)->format('Y-m-d H:i:s'),
        ])->assertCreated()->assertJsonPath('shipment.delay.severity', 'HIGH');

        // back to plan => not delayed
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'event_type' => 'ETA_UPDATE',
            'estimated_arrival_at' => $expected->format('Y-m-d H:i:s'),
        ])->assertCreated()->assertJsonPath('shipment.delay.is_delayed', false);
    }

    public function test_eta_update_requires_a_new_eta(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", ['event_type' => 'ETA_UPDATE'])
            ->assertStatus(422)->assertJsonValidationErrors('estimated_arrival_at');
    }

    public function test_delay_filter_works_server_side(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $late = $this->makeShipment($customer, $operator);
        $this->makeShipment($customer, $operator);
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$late->id}/tracking", [
            'event_type' => 'ETA_UPDATE',
            'estimated_arrival_at' => $late->expected_arrival_at->copy()->addHours(3)->format('Y-m-d H:i:s'),
        ])->assertCreated();

        $this->getJson('/api/shipments?delayed=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $late->id);
        $this->getJson('/api/shipments?delayed=0')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/shipments?severity=LOW')->assertOk()->assertJsonCount(1, 'data');
    }
}
