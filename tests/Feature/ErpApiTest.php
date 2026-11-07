<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class ErpApiTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private const KEY = 'erp-test-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.erp.api_key' => self::KEY]);
    }

    private function erp(string $url)
    {
        return $this->getJson($url, ['X-ERP-KEY' => self::KEY]);
    }

    public function test_requests_without_the_key_are_rejected(): void
    {
        $this->getJson('/api/erp/shipments')->assertUnauthorized();
        $this->getJson('/api/erp/payments')->assertUnauthorized();
    }

    public function test_wrong_key_is_rejected(): void
    {
        $this->getJson('/api/erp/shipments', ['X-ERP-KEY' => 'wrong'])->assertUnauthorized();
    }

    public function test_endpoints_are_disabled_when_no_key_is_configured(): void
    {
        config(['services.erp.api_key' => '']);

        $this->getJson('/api/erp/shipments', ['X-ERP-KEY' => ''])->assertUnauthorized();
        $this->getJson('/api/erp/shipments')->assertUnauthorized();
    }

    public function test_a_logged_in_admin_still_needs_the_erp_key(): void
    {
        Sanctum::actingAs($this->makeUser(User::ROLE_ADMIN));

        $this->getJson('/api/erp/shipments')->assertUnauthorized();
    }

    public function test_valid_key_lists_shipments_with_related_data(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);

        $this->erp('/api/erp/shipments')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $shipment->id)
            ->assertJsonPath('data.0.customer.email', $customer->email);
    }

    public function test_updated_since_filters_out_older_shipments(): void
    {
        $this->makeShipment($this->makeUser(User::ROLE_CUSTOMER), $this->makeUser(User::ROLE_OPERATOR));

        $future = urlencode(now()->addDay()->toDateTimeString());
        $this->erp("/api/erp/shipments?updated_since={$future}")->assertOk()->assertJsonPath('total', 0);
    }

    public function test_per_page_is_capped_at_200(): void
    {
        $this->erp('/api/erp/shipments?per_page=5000')->assertOk()->assertJsonPath('per_page', 200);
    }

    public function test_payments_can_be_filtered_by_status_case_insensitively(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);
        foreach (['PAID', 'PENDING'] as $i => $status) {
            Payment::create([
                'shipment_id' => $shipment->id, 'user_id' => $customer->id,
                'order_id' => "CT-ERP-{$i}", 'amount' => 100000, 'status' => $status,
            ]);
        }

        $this->erp('/api/erp/payments')->assertOk()->assertJsonPath('total', 2);
        $this->erp('/api/erp/payments?status=paid')->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.status', 'PAID')
            ->assertJsonPath('data.0.shipment.shipment_number', $shipment->shipment_number);
    }
}
