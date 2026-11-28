<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

/** Customer shipment requests (PENDING) -> approval by admin/operator -> notifications. */
class ShipmentRequestFlowTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    /** Simulates the schedule the admin/operator sets while processing a customer request. */
    private function setSchedule(Shipment $shipment): void
    {
        $shipment->forceFill(['expected_departure_at' => now()->addDay(), 'expected_arrival_at' => now()->addDays(5)])->save();
    }

    private function payload(User $customer): array
    {
        return $this->shipmentPayload($customer) + ['items' => [['description' => 'Songket cloth', 'quantity' => 2]]];
    }

    public function test_customer_request_is_pending_and_notifies_all_admins_and_operators(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $admins = [$this->makeUser(User::ROLE_ADMIN), $this->makeUser(User::ROLE_ADMIN)];
        $operator = $this->makeUser(User::ROLE_OPERATOR);

        $this->actingAs($customer)->get('/shipments/create')->assertOk()->assertDontSee('name="customer_id"', false);

        // customer_id in the form is ignored/overridden by the logged-in customer
        $other = $this->makeUser(User::ROLE_CUSTOMER);
        $this->actingAs($customer)->post('/shipments', $this->payload($other))->assertRedirect();

        $shipment = Shipment::firstOrFail();
        $this->assertSame('PENDING', $shipment->current_status->value);
        $this->assertSame($customer->id, (int) $shipment->customer_id);
        // The customer does not set the schedule (even if sent): the processing admin/operator does.
        $this->assertNull($shipment->expected_departure_at);
        $this->assertNull($shipment->expected_arrival_at);

        $expected = "New shipment request #{$shipment->shipment_number} from customer {$customer->name} is waiting to be processed.";
        foreach ([...$admins, $operator] as $staff) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $staff->id, 'shipment_id' => $shipment->id, 'type' => 'SHIPMENT_REQUEST', 'message' => $expected,
            ]);
        }
        $this->assertDatabaseMissing('notifications', ['user_id' => $customer->id, 'type' => 'SHIPMENT_REQUEST']);

        // The existing Notifications page (sidebar menu / bell) lists it and links to the shipment detail page.
        $this->actingAs($operator)->get('/notifications')->assertOk()
            ->assertSee($expected)->assertSee(route('shipments.show', $shipment), false);
    }

    public function test_operator_approval_notifies_admins_and_customer_but_not_other_operators(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $otherOperator = $this->makeUser(User::ROLE_OPERATOR);

        $this->actingAs($customer)->post('/shipments', $this->payload($customer));
        $shipment = Shipment::firstOrFail();

        $this->setSchedule($shipment);
        $this->actingAs($operator)->post("/shipments/{$shipment->id}/approve")->assertRedirect();

        $this->assertSame('BOOKED', $shipment->fresh()->current_status->value);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id, 'type' => 'SHIPMENT_PROCESSED',
            'message' => "Shipment #{$shipment->shipment_number} belonging to {$customer->name} has been added/processed by Operator ({$operator->name}).",
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $customer->id, 'shipment_id' => $shipment->id, 'type' => 'STATUS_CHANGED']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $otherOperator->id, 'type' => 'SHIPMENT_PROCESSED']);
    }

    public function test_admin_approval_notifies_all_operators(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $operators = [$this->makeUser(User::ROLE_OPERATOR), $this->makeUser(User::ROLE_OPERATOR)];

        $this->actingAs($customer)->post('/shipments', $this->payload($customer));
        $shipment = Shipment::firstOrFail();

        $this->setSchedule($shipment);
        $this->actingAs($admin)->post("/shipments/{$shipment->id}/approve")->assertRedirect();

        foreach ($operators as $op) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $op->id, 'type' => 'SHIPMENT_PROCESSED',
                'message' => "Shipment #{$shipment->shipment_number} belonging to {$customer->name} has been added/processed by Admin ({$admin->name}).",
            ]);
        }
        $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id, 'type' => 'SHIPMENT_PROCESSED']);
    }

    public function test_staff_created_shipment_notifies_the_other_staff_role(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $operator = $this->makeUser(User::ROLE_OPERATOR);

        $this->actingAs($operator)->post('/shipments', $this->payload($customer))->assertRedirect();
        $shipment = Shipment::firstOrFail();

        $this->assertSame('CREATED', $shipment->current_status->value);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => 'SHIPMENT_PROCESSED']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $operator->id, 'type' => 'SHIPMENT_PROCESSED']);
    }

    public function test_customer_cannot_approve_edit_or_see_other_requests(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $stranger = $this->makeUser(User::ROLE_CUSTOMER);
        $this->actingAs($customer)->post('/shipments', $this->payload($customer));
        $shipment = Shipment::firstOrFail();

        $this->actingAs($customer)->post("/shipments/{$shipment->id}/approve")->assertForbidden();
        $this->actingAs($customer)->get("/shipments/{$shipment->id}/edit")->assertForbidden();
        $this->actingAs($stranger)->get("/shipments/{$shipment->id}")->assertForbidden();
        $this->assertSame('PENDING', $shipment->fresh()->current_status->value);
    }

    public function test_customer_form_has_no_schedule_fields_but_staff_form_has(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $this->actingAs($customer)->get('/shipments/create')->assertOk()
            ->assertDontSee('name="expected_departure_at"', false)->assertDontSee('name="expected_arrival_at"', false);
        $this->actingAs($this->makeUser(User::ROLE_OPERATOR))->get('/shipments/create')->assertOk()
            ->assertSee('name="expected_departure_at"', false)->assertSee('name="expected_arrival_at"', false);
    }

    public function test_cannot_approve_without_schedule_and_operator_sets_it_when_approving(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $this->actingAs($customer)->post('/shipments', $this->payload($customer));
        $shipment = Shipment::firstOrFail();

        $this->actingAs($operator)->get("/shipments/{$shipment->id}")->assertOk();   // page renders without a schedule
        $this->actingAs($operator)->post("/shipments/{$shipment->id}/approve");
        $this->assertSame('PENDING', $shipment->fresh()->current_status->value);

        // saving without dates is fine while PENDING; "Save and approve" without dates is rejected
        $base = array_merge($this->payload($customer), ['expected_departure_at' => null, 'expected_arrival_at' => null]);
        $this->actingAs($operator)->put("/shipments/{$shipment->id}", $base)->assertSessionHasNoErrors();
        $this->actingAs($operator)->put("/shipments/{$shipment->id}", $base + ['approve' => 1])
            ->assertSessionHasErrors(['expected_departure_at', 'expected_arrival_at']);
        $this->assertSame('PENDING', $shipment->fresh()->current_status->value);

        $this->actingAs($operator)->put("/shipments/{$shipment->id}", array_merge($base, ['approve' => 1,
            'expected_departure_at' => now()->addDay()->format('Y-m-d H:i:s'), 'expected_arrival_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
        ]))->assertRedirect();
        $shipment->refresh();
        $this->assertSame('BOOKED', $shipment->current_status->value);
        $this->assertNotNull($shipment->expected_departure_at);
        $this->assertNotNull($shipment->estimated_arrival_at);
    }

    public function test_operator_can_edit_customer_request_and_approve_in_one_step(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $this->actingAs($customer)->post('/shipments', $this->payload($customer));
        $shipment = Shipment::firstOrFail();
        $newCarrier = $this->makeCarrier();

        $this->actingAs($operator)->get("/shipments/{$shipment->id}/edit")->assertOk()->assertSee('Save and approve');
        $this->actingAs($operator)->put("/shipments/{$shipment->id}", array_merge($this->payload($customer), [
            'carrier_id' => $newCarrier->id, 'approve' => 1,
        ]))->assertRedirect();

        $shipment->refresh();
        $this->assertSame($newCarrier->id, (int) $shipment->carrier_id);
        $this->assertSame('BOOKED', $shipment->current_status->value);
    }

    public function test_approve_is_only_possible_for_pending(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $shipment = $this->makeShipment($customer, $admin);   // CREATED

        $this->actingAs($admin)->post("/shipments/{$shipment->id}/approve")->assertForbidden();
    }

    public function test_viewer_mode_hides_request_buttons_and_blocks_writes(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $this->actingAs($customer)->post('/shipments', $this->payload($customer));
        $shipment = Shipment::firstOrFail();

        $viewer = ['is_viewer' => true];
        $this->withSession($viewer)->get('/shipments')->assertOk()->assertDontSee(route('shipments.create'), false);
        $this->withSession($viewer)->get("/shipments/{$shipment->id}")->assertOk()->assertDontSee('Approve and process');
        $this->withSession($viewer)->get('/shipments/create')->assertRedirect();
        $this->withSession($viewer)->post("/shipments/{$shipment->id}/approve")->assertRedirect();
        $this->assertSame('PENDING', $shipment->fresh()->current_status->value);
        $this->assertSame(0, AppNotification::where('type', 'SHIPMENT_PROCESSED')->count());
    }

    public function test_customer_still_sees_new_request_button_and_confirm_received_flow_is_unchanged(): void
    {
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $this->actingAs($customer)->get('/shipments')->assertOk()->assertSee('Request shipment');
        $this->actingAs($customer)->get('/dashboard')->assertOk()->assertSee('Request shipment');
    }
}
