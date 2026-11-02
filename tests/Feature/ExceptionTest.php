<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class ExceptionTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function payload(): array
    {
        return ['type' => 'PORT_CONGESTION', 'severity' => 'MEDIUM', 'description' => 'Queue at berth.'];
    }

    public function test_operator_opens_exception_with_detection_time_and_timeline_marker(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        Sanctum::actingAs($operator);

        $this->postJson("/api/shipments/{$shipment->id}/exceptions", $this->payload())
            ->assertCreated()->assertJsonPath('data.status', 'OPEN')->assertJsonPath('data.resolved_at', null);

        $this->assertNotNull($shipment->exceptions()->first()->detected_at);
        $this->assertDatabaseHas('tracking_events', ['shipment_id' => $shipment->id, 'event_type' => 'EXCEPTION', 'is_exception' => true]);
    }

    public function test_validation_and_authorization(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);

        Sanctum::actingAs($operator);
        $this->postJson("/api/shipments/{$shipment->id}/exceptions", ['type' => 'ALIENS', 'severity' => 'HIGH', 'description' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('type');

        Sanctum::actingAs($customer);
        $this->postJson("/api/shipments/{$shipment->id}/exceptions", $this->payload())->assertForbidden();
        $this->getJson("/api/shipments/{$shipment->id}/exceptions")->assertOk();
    }

    public function test_status_moves_forward_and_resolution_time_is_stored(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        Sanctum::actingAs($operator);

        $id = $this->postJson("/api/shipments/{$shipment->id}/exceptions", $this->payload())->json('data.id');

        $this->patchJson("/api/exceptions/{$id}", ['status' => 'INVESTIGATING'])->assertOk()->assertJsonPath('data.status', 'INVESTIGATING');
        $this->patchJson("/api/exceptions/{$id}", ['status' => 'RESOLVED', 'resolution_notes' => 'Berth freed.'])
            ->assertOk()->assertJsonPath('data.status', 'RESOLVED');

        $this->assertNotNull($shipment->exceptions()->first()->resolved_at);

        // RESOLVED is final
        $this->patchJson("/api/exceptions/{$id}", ['status' => 'OPEN'])->assertStatus(422);
        $this->patchJson("/api/exceptions/{$id}", ['status' => 'INVESTIGATING'])->assertStatus(422);
    }

    public function test_customer_cannot_update_exception(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);
        Sanctum::actingAs($operator);
        $id = $this->postJson("/api/shipments/{$shipment->id}/exceptions", $this->payload())->json('data.id');

        Sanctum::actingAs($customer);
        $this->patchJson("/api/exceptions/{$id}", ['status' => 'RESOLVED'])->assertForbidden();
    }
}
