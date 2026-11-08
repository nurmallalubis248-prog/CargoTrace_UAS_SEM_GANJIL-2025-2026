<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

/** Data the browser needs to draw a live route and move the vehicle toward the ETA. */
class LiveMapDataTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function inTransit()
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);
        $shipment->forceFill([
            'current_status' => 'IN_TRANSIT', 'current_latitude' => 3.5, 'current_longitude' => 98.7,
            'last_event_at' => now()->subHour(), 'estimated_arrival_at' => now()->addDays(2),
        ])->save();

        return $shipment;
    }

    public function test_dashboard_positions_carry_origin_eta_and_last_scan_time(): void
    {
        $shipment = $this->inTransit();
        Sanctum::actingAs($this->makeUser(User::ROLE_ADMIN));

        $p = $this->getJson('/api/dashboard')->assertOk()->json('positions.0');

        $this->assertEquals($shipment->origin->latitude, $p['origin_lat']);
        $this->assertEquals($shipment->origin->longitude, $p['origin_lng']);
        $this->assertIsInt($p['last_ts']);
        $this->assertIsInt($p['eta_ts']);
        $this->assertGreaterThan($p['last_ts'], $p['eta_ts']);
    }

    public function test_shipment_page_embeds_the_live_route_data_and_script(): void
    {
        $shipment = $this->inTransit();

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))->get("/shipments/{$shipment->id}")
            ->assertOk()
            ->assertSee('"eta_ts"', false)
            ->assertSee('"last_ts"', false)
            ->assertSee('ct-live.js', false);
    }
}
