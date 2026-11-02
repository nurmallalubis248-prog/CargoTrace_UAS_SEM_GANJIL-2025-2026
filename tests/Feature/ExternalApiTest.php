<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class ExternalApiTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function shipmentFor(User $customer, User $operator)
    {
        return $this->makeShipment($customer, $operator);
    }

    public function test_weather_success(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response([
            'current' => ['time' => '2026-11-02T10:00', 'temperature_2m' => 29.5, 'wind_speed_10m' => 12.0, 'precipitation' => 0.2, 'weather_code' => 3],
        ], 200)]);

        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->shipmentFor($customer, $this->makeUser(User::ROLE_OPERATOR));
        Sanctum::actingAs($customer);

        $this->getJson("/api/shipments/{$shipment->id}/weather")->assertOk()
            ->assertJsonPath('available', true)->assertJsonPath('condition', 'Overcast')
            ->assertJsonPath('temperature_c', 29.5)->assertJsonStructure(['disclaimer', 'location' => ['label']]);
    }

    public function test_weather_provider_error_is_handled_gracefully(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response(['error' => true], 500)]);

        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->shipmentFor($customer, $this->makeUser(User::ROLE_OPERATOR));
        Sanctum::actingAs($customer);

        $this->getJson("/api/shipments/{$shipment->id}/weather")->assertOk()->assertJsonPath('available', false);
    }

    public function test_weather_timeout_is_handled_gracefully(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timeout'));

        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->shipmentFor($customer, $this->makeUser(User::ROLE_OPERATOR));
        Sanctum::actingAs($customer);

        $this->getJson("/api/shipments/{$shipment->id}/weather")->assertOk()
            ->assertJsonPath('available', false)->assertJsonPath('message', fn ($m) => str_contains($m, 'unreachable'));
    }

    public function test_weather_empty_payload_is_handled_gracefully(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response([], 200)]);

        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->shipmentFor($customer, $this->makeUser(User::ROLE_OPERATOR));
        Sanctum::actingAs($customer);

        $this->getJson("/api/shipments/{$shipment->id}/weather")->assertOk()->assertJsonPath('available', false);
    }

    public function test_weather_respects_shipment_authorization(): void
    {
        Http::fake();
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $shipment = $this->shipmentFor($this->makeUser(User::ROLE_CUSTOMER), $operator);

        Sanctum::actingAs($this->makeUser(User::ROLE_CUSTOMER));
        $this->getJson("/api/shipments/{$shipment->id}/weather")->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_geocoding_for_staff(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
            ['display_name' => 'Singapore', 'lat' => '1.3521', 'lon' => '103.8198'],
        ], 200)]);

        Sanctum::actingAs($this->makeUser(User::ROLE_OPERATOR));
        $this->getJson('/api/geocode?q=Singapore')->assertOk()
            ->assertJsonPath('available', true)->assertJsonPath('results.0.latitude', 1.3521);

        $this->getJson('/api/geocode?q=ab')->assertStatus(422);
    }

    public function test_geocoding_provider_error_is_handled_gracefully(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response('', 503)]);

        Sanctum::actingAs($this->makeUser(User::ROLE_OPERATOR));
        $this->getJson('/api/geocode?q=Rotterdam')->assertOk()->assertJsonPath('available', false);
    }

    public function test_geocoding_is_forbidden_for_customers(): void
    {
        Sanctum::actingAs($this->makeUser(User::ROLE_CUSTOMER));
        $this->getJson('/api/geocode?q=Singapore')->assertForbidden();
    }
}
