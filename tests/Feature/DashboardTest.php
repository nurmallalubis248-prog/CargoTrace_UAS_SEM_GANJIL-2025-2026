<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_dashboard_numbers_come_from_the_database_and_are_scoped_by_role(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $alice = $this->makeUser(User::ROLE_CUSTOMER);
        $bob = $this->makeUser(User::ROLE_CUSTOMER);

        $a1 = $this->makeShipment($alice, $operator);
        $this->makeShipment($alice, $operator);
        $this->makeShipment($bob, $operator);

        Sanctum::actingAs($operator);
        foreach (['BOOKED', 'PICKED_UP', 'IN_TRANSIT'] as $s) {
            $this->postJson("/api/shipments/{$a1->id}/tracking", ['event_type' => 'STATUS_UPDATE', 'status' => $s])->assertCreated();
        }
        $this->postJson("/api/shipments/{$a1->id}/exceptions", ['type' => 'DAMAGED', 'severity' => 'LOW', 'description' => 'Dent.'])->assertCreated();

        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('totals.shipments', 3)
            ->assertJsonPath('totals.in_transit', 1)
            ->assertJsonPath('totals.open_exceptions', 1)
            ->assertJsonPath('by_status.CREATED', 2)
            ->assertJsonCount(1, 'positions');

        Sanctum::actingAs($alice);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('totals.shipments', 2);

        Sanctum::actingAs($bob);
        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('totals.shipments', 1)->assertJsonPath('totals.open_exceptions', 0);
    }

    public function test_dashboard_with_no_data_does_not_fail(): void
    {
        Sanctum::actingAs($this->makeUser(User::ROLE_ADMIN));

        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('totals.shipments', 0)->assertJsonPath('rates.delay_rate_pct', 0);
    }

    public function test_web_pages_render_for_each_role_and_enforce_access(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $shipment = $this->makeShipment($customer, $operator);

        $this->actingAs($customer)->get('/dashboard')->assertOk();
        $this->actingAs($customer)->get('/shipments')->assertOk()->assertSee($shipment->shipment_number);
        $this->actingAs($customer)->get("/shipments/{$shipment->id}")->assertOk()->assertSee('Tracking timeline');
        $this->actingAs($customer)->get('/shipments/create')->assertForbidden();
        $this->actingAs($customer)->get('/admin/users')->assertForbidden();
        $this->actingAs($customer)->get('/admin/audit-logs')->assertForbidden();

        $this->actingAs($operator)->get('/shipments/create')->assertOk();
        $this->actingAs($operator)->get('/admin/carriers')->assertForbidden();

        foreach (['/admin/carriers', '/admin/locations', '/admin/users', '/admin/audit-logs', '/notifications'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $other = $this->makeUser(User::ROLE_CUSTOMER);
        $this->actingAs($other)->get("/shipments/{$shipment->id}")->assertForbidden();
    }

    public function test_web_business_rule_violation_redirects_back_with_error_instead_of_500(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);

        $this->actingAs($operator)->from("/shipments/{$shipment->id}")
            ->post("/shipments/{$shipment->id}/tracking", ['event_type' => 'STATUS_UPDATE', 'status' => 'DELIVERED'])
            ->assertRedirect("/shipments/{$shipment->id}")
            ->assertSessionHas('error');
    }
}
