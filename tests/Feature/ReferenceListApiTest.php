<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

/** GET /api/carriers and GET /api/locations: server-side pagination, filters and validation. */
class ReferenceListApiTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_carriers_are_paginated_and_filterable(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Carrier::create(['name' => 'Carrier '.$i, 'code' => 'C'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'transport_mode' => 'SEA']);
        }
        Carrier::create(['name' => 'Zebra Air Cargo', 'code' => 'ZBR', 'transport_mode' => 'AIR']);
        Sanctum::actingAs($this->makeUser(User::ROLE_OPERATOR));

        $this->getJson('/api/carriers?per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 31);

        $this->getJson('/api/carriers?q=zebra')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'ZBR');
        $this->getJson('/api/carriers?transport_mode=AIR')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/carriers?per_page=500')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    public function test_locations_are_paginated_and_filterable(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Location::create(['name' => 'Port '.$i, 'code' => 'PT'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'country' => 'ID', 'type' => 'PORT', 'latitude' => 1.0, 'longitude' => 103.0]);
        }
        Location::create(['name' => 'Test Airport', 'code' => 'TAIR1', 'country' => 'SG', 'type' => 'AIRPORT', 'latitude' => 1.3, 'longitude' => 103.9]);
        Sanctum::actingAs($this->makeUser(User::ROLE_ADMIN));

        $this->getJson('/api/locations?per_page=5')->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.total', 13);
        $this->getJson('/api/locations?type=AIRPORT')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/locations?country=sg')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/locations?type=INVALID')->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_customers_still_cannot_list_carriers_or_locations(): void
    {
        Sanctum::actingAs($this->makeUser(User::ROLE_CUSTOMER));

        $this->getJson('/api/carriers')->assertForbidden();
        $this->getJson('/api/locations')->assertForbidden();
    }
}
