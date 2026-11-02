<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_operator_can_create_shipment_and_initial_event_is_recorded(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        Sanctum::actingAs($operator);

        $response = $this->postJson('/api/shipments', $this->shipmentPayload($customer, [
            'items' => [['description' => 'Pallets of tea', 'quantity' => 4]],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'CREATED')
            ->assertJsonPath('data.delay.is_delayed', false)
            ->assertJsonCount(1, 'data.items');

        $id = $response->json('data.id');
        $this->getJson("/api/shipments/{$id}/tracking")->assertOk()->assertJsonCount(1, 'data');
        $this->assertDatabaseHas('audit_logs', ['action' => 'shipment.created', 'entity_id' => $id, 'user_id' => $operator->id]);
    }

    public function test_customer_cannot_create_shipment(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        Sanctum::actingAs($customer);

        $this->postJson('/api/shipments', $this->shipmentPayload($customer))->assertForbidden();
    }

    public function test_validation_rejects_bad_data(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        Sanctum::actingAs($operator);

        $payload = $this->shipmentPayload($customer, [
            'weight_kg' => -5,
            'cargo_type' => 'SPACESHIP',
            'expected_arrival_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);
        $payload['destination_id'] = $payload['origin_id'];

        $this->postJson('/api/shipments', $payload)->assertStatus(422)
            ->assertJsonValidationErrors(['weight_kg', 'cargo_type', 'expected_arrival_at', 'destination_id']);
    }

    public function test_customer_only_sees_and_opens_own_shipments(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $alice = $this->makeUser(User::ROLE_CUSTOMER);
        $bob = $this->makeUser(User::ROLE_CUSTOMER);
        $mine = $this->makeShipment($alice, $operator);
        $theirs = $this->makeShipment($bob, $operator);

        Sanctum::actingAs($alice);

        $this->getJson('/api/shipments')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
        $this->getJson("/api/shipments/{$mine->id}")->assertOk();
        $this->getJson("/api/shipments/{$theirs->id}")->assertForbidden();
        $this->getJson("/api/shipments/{$theirs->id}/tracking")->assertForbidden();
    }

    public function test_staff_see_all_shipments_with_server_side_pagination_and_filters(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        foreach (range(1, 5) as $i) {
            $this->makeShipment($customer, $operator, ['reference_number' => "PO-{$i}"]);
        }
        Sanctum::actingAs($operator);

        $this->getJson('/api/shipments?per_page=2')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 5)->assertJsonPath('meta.per_page', 2);

        $this->getJson('/api/shipments?q=PO-3')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/shipments?status=DELIVERED')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/shipments?status=NOT_A_STATUS')->assertStatus(422);
        $this->getJson('/api/shipments?sort=shipment_number&dir=asc')->assertOk();
    }

    public function test_operator_can_only_update_shipments_they_created(): void
    {
        $owner = $this->makeUser(User::ROLE_OPERATOR);
        $other = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $owner);

        Sanctum::actingAs($other);
        $this->patchJson("/api/shipments/{$shipment->id}", ['weight_kg' => 2000])->assertForbidden();

        Sanctum::actingAs($owner);
        $this->patchJson("/api/shipments/{$shipment->id}", ['weight_kg' => 2000])->assertOk()->assertJsonPath('data.weight_kg', 2000);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/shipments/{$shipment->id}", ['weight_kg' => 2500])->assertOk();
    }

    public function test_status_cannot_be_changed_through_update_endpoint(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $admin);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/shipments/{$shipment->id}", ['current_status' => 'DELIVERED', 'status' => 'DELIVERED'])->assertOk();

        $this->assertSame('CREATED', $shipment->fresh()->current_status->value);
    }

    public function test_only_admin_can_delete_and_only_when_created(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);

        Sanctum::actingAs($operator);
        $this->deleteJson("/api/shipments/{$shipment->id}")->assertForbidden();

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/shipments/{$shipment->id}")->assertOk();
        $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
        $this->getJson("/api/shipments/{$shipment->id}")->assertNotFound()->assertJsonPath('message', 'Resource not found.');
    }

    public function test_carrier_and_location_endpoints_are_role_protected(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);

        Sanctum::actingAs($customer);
        $this->getJson('/api/carriers')->assertForbidden();

        Sanctum::actingAs($operator);
        $this->getJson('/api/carriers')->assertOk();
        $this->postJson('/api/carriers', ['name' => 'X', 'code' => 'XX'])->assertForbidden();

        Sanctum::actingAs($admin);
        $this->postJson('/api/carriers', ['name' => 'Test Line', 'code' => 'tl01'])->assertCreated()->assertJsonPath('data.code', 'TL01');
        $this->postJson('/api/locations', [
            'name' => 'Test Port', 'code' => 'tp001', 'country' => 'id', 'type' => 'PORT', 'latitude' => -6.1, 'longitude' => 106.8,
        ])->assertCreated();
        $this->postJson('/api/locations', ['name' => 'Bad', 'code' => 'bad', 'country' => 'id', 'type' => 'PORT', 'latitude' => 200, 'longitude' => 0])
            ->assertStatus(422)->assertJsonValidationErrors('latitude');
    }
}
