<?php

namespace Tests\Feature;

use App\Enums\ExceptionStatus;
use App\Models\User;
use App\Services\ExceptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class DelayReasonTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function lateShipment(User $customer, User $operator)
    {
        $s = $this->makeShipment($customer, $operator);
        $s->forceFill(['is_delayed' => true, 'delay_hours' => 20, 'delay_severity' => 'HIGH'])->save();

        return $s;
    }

    private function report($shipment, User $operator, string $type, string $text)
    {
        return app(ExceptionService::class)->open($shipment, ['type' => $type, 'severity' => 'HIGH', 'description' => $text], $operator);
    }

    public function test_no_reason_when_not_delayed_or_nothing_explains_the_delay(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);

        $this->assertNull($this->makeShipment($customer, $operator)->delayReason());
        $this->assertNull($this->lateShipment($customer, $operator)->delayReason());
    }

    public function test_reason_comes_from_the_exception_type(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->lateShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        $this->report($shipment, $operator, 'DOCUMENTATION_ISSUE', 'Packing list missing');

        $reason = $shipment->fresh()->delayReason();

        $this->assertSame('Incomplete documents', $reason['label']);
        $this->assertSame('Packing list missing', $reason['description']);
        $this->assertTrue($reason['open']);
    }

    public function test_an_open_exception_wins_over_a_resolved_one(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->lateShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        $old = $this->report($shipment, $operator, 'CUSTOMS_HOLD', 'Inspection');
        $old->forceFill(['status' => ExceptionStatus::RESOLVED, 'resolved_at' => now()])->save();
        $this->report($shipment, $operator, 'WEATHER_DELAY', 'Storm');

        $this->assertSame('Bad weather', $shipment->fresh()->delayReason()['label']);
    }

    public function test_dashboard_lists_delayed_shipments_with_route_and_reason(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $shipment = $this->lateShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        $this->report($shipment, $operator, 'CUSTOMS_HOLD', 'Waiting for inspection');

        Sanctum::actingAs($admin);
        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('delayed_list.0.number', $shipment->shipment_number)
            ->assertJsonPath('delayed_list.0.reason', 'Held at customs')
            ->assertJsonPath('delayed_list.0.delay_hours', 20)
            ->assertJsonPath('delayed_list.0.route', $shipment->origin->name.' → '.$shipment->destination->name);
    }

    public function test_shipment_list_can_filter_by_open_exception_and_shows_the_reason(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $plain = $this->makeShipment($customer, $operator);
        $problem = $this->lateShipment($customer, $operator);
        $this->report($problem, $operator, 'DOCUMENTATION_ISSUE', 'Invoice missing');

        $this->actingAs($admin)->get('/shipments?exception=open')->assertOk()
            ->assertSee($problem->shipment_number)
            ->assertSee('Incomplete documents')
            ->assertDontSee($plain->shipment_number);
    }
}
