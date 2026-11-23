<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peta global (CesiumJS). Halaman hanya menyajikan kerangka + konfigurasi dari .env;
 * data pengiriman datang dari endpoint dashboard.stats / dashboard.locations yang sudah dibatasi per peran.
 */
class GlobeMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/map')->assertRedirect('/login');
    }

    public function test_a_verified_user_can_open_the_globe_map(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/map')
            ->assertOk()
            ->assertSee('gl-cesium', false)
            ->assertSee('ct-globe.js', false)
            // @json escapes "/" as "\/", so compare the escaped form
            ->assertSee(str_replace('/', '\\/', route('dashboard.stats')), false)
            ->assertSee(str_replace('/', '\\/', route('dashboard.locations')), false);
    }

    public function test_features_that_need_keys_are_disabled_when_the_keys_are_empty(): void
    {
        config(['services.cesium.ion_token' => null, 'services.google_maps.api_key' => null]);

        $this->actingAs(User::factory()->create())
            ->get('/map')
            ->assertOk()
            ->assertSee('"ionToken":null', false)
            ->assertSee('"googleKey":null', false);
    }

    public function test_configured_keys_are_passed_to_the_browser(): void
    {
        config(['services.cesium.ion_token' => 'ion-test-token', 'services.google_maps.api_key' => 'google-test-key']);

        $this->actingAs(User::factory()->create())
            ->get('/map')
            ->assertOk()
            ->assertSee('ion-test-token', false)
            ->assertSee('google-test-key', false);
    }

    public function test_the_shipment_query_parameter_is_only_accepted_as_a_number(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/map?shipment=<script>alert(1)</script>')
            ->assertOk()
            ->assertSee('"focusShipmentId":null', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_the_map_link_is_in_the_navigation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('map.globe'), false);
    }
}
