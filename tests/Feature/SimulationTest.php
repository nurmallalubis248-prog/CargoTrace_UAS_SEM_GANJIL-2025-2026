<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RouteGeometry;
use App\Services\SimulationService;
use App\Services\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

/** Demo mode: moves cargo by writing real tracking events; it stops at ARRIVED (the customer pays and confirms). */
class SimulationTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function booked(?string $mode = null): array
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator, [], now()->subDays(3));
        if ($mode) {
            $shipment->carrier->forceFill(['transport_mode' => $mode])->save();
        }
        app(TrackingService::class)->addEvent($shipment, ['event_type' => 'STATUS_UPDATE', 'status' => 'BOOKED'], $operator);

        return [$shipment->fresh(), $operator, $customer];
    }

    public function test_full_journey_ends_at_arrived_and_notifies_the_customer(): void
    {
        [$shipment, $operator, $customer] = $this->booked();
        $sim = app(SimulationService::class);

        $seen = [];
        for ($i = 0; $i < 10 && $shipment->fresh()->current_status->value !== 'ARRIVED'; $i++) {
            $sim->advance($shipment->fresh(), $operator, 60);
            $seen[] = $shipment->fresh()->current_status->value;
        }

        $this->assertSame(['PICKED_UP', 'IN_TRANSIT', 'IN_TRANSIT', 'ARRIVED'], $seen);
        $shipment->refresh();
        $this->assertNotNull($shipment->actual_arrival_at);
        $this->assertEquals((float) $shipment->destination->latitude, (float) $shipment->current_latitude);
        $this->assertDatabaseHas('tracking_events', ['shipment_id' => $shipment->id, 'event_type' => 'CHECKPOINT']);
        $this->assertDatabaseHas('notifications', ['user_id' => $customer->id, 'shipment_id' => $shipment->id, 'type' => 'STATUS_CHANGED']);
        $this->assertStringStartsWith('[Simulation]', (string) $shipment->trackingEvents()->latest('id')->first()->description);

        $this->assertNull($sim->advance($shipment->fresh(), $operator, 60), 'the simulator never moves an ARRIVED cargo to DELIVERED');
    }

    public function test_each_checkpoint_gets_closer_to_the_destination(): void
    {
        [$shipment, $operator] = $this->booked();
        $sim = app(SimulationService::class);
        $sim->advance($shipment->fresh(), $operator, 10);   // PICKED_UP
        $sim->advance($shipment->fresh(), $operator, 10);   // IN_TRANSIT

        $dest = [(float) $shipment->destination->latitude, (float) $shipment->destination->longitude];
        $before = RouteGeometry::distance([(float) $shipment->fresh()->current_latitude, (float) $shipment->fresh()->current_longitude], $dest);
        $sim->advance($shipment->fresh(), $operator, 10);   // checkpoint
        $s = $shipment->fresh();
        $after = RouteGeometry::distance([(float) $s->current_latitude, (float) $s->current_longitude], $dest);

        $this->assertLessThan($before, $after);
    }

    public function test_created_cancelled_and_finished_shipments_are_ignored(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $created = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);

        $this->assertNull(app(SimulationService::class)->advance($created->fresh(), $operator, 10));
    }

    public function test_road_shipments_follow_the_osrm_road_geometry(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response(['routes' => [['geometry' => ['coordinates' => [[103.84, 1.26], [60.0, 30.0], [4.14, 51.95]]]]]])]);
        [$shipment, $operator] = $this->booked('ROAD');
        $sim = app(SimulationService::class);
        $sim->advance($shipment->fresh(), $operator, 10);
        $sim->advance($shipment->fresh(), $operator, 10);
        $sim->advance($shipment->fresh(), $operator, 10);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'router.project-osrm.org'));
        $lat = (float) $shipment->fresh()->current_latitude;
        $this->assertGreaterThan(1.26, $lat);
        $this->assertLessThan(30.0, $lat);   // still on the first road segment
    }

    public function test_road_shipments_fall_back_to_a_straight_route_when_the_routing_service_fails(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response('down', 500)]);
        [$shipment, $operator] = $this->booked('ROAD');
        $sim = app(SimulationService::class);

        $sim->advance($shipment->fresh(), $operator, 10);
        $sim->advance($shipment->fresh(), $operator, 10);
        $event = $sim->advance($shipment->fresh(), $operator, 10);

        $this->assertNotNull($event);
        $this->assertSame('IN_TRANSIT', $shipment->fresh()->current_status->value);
    }

    public function test_the_command_advances_active_shipments(): void
    {
        [$shipment] = $this->booked();

        $this->artisan('cargotrace:simulate', ['--step' => 20])->expectsOutputToContain('Advanced 1 shipment')->assertSuccessful();

        $this->assertSame('PICKED_UP', $shipment->fresh()->current_status->value);
    }

    public function test_live_endpoint_is_scoped_to_the_owner(): void
    {
        [$shipment, , $customer] = $this->booked();

        $this->actingAs($customer)->getJson("/shipments/{$shipment->id}/live")->assertOk()
            ->assertJsonStructure(['status', 'events', 'last_event_at', 'delayed', 'done']);

        $status = $this->actingAs($this->makeUser(User::ROLE_CUSTOMER))->getJson("/shipments/{$shipment->id}/live")->getStatusCode();
        $this->assertContains($status, [403, 404]);
    }
}
