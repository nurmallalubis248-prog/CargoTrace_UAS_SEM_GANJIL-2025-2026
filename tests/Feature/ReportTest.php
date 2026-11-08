<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function lateShipment($customer, $operator)
    {
        $s = $this->makeShipment($customer, $operator);
        $s->forceFill(['is_delayed' => true, 'delay_hours' => 20, 'delay_severity' => 'HIGH'])->save();

        return $s;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/reports/pdf')->assertRedirect('/login');
    }

    public function test_customer_report_only_lists_their_own_shipments(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $alice = $this->makeUser(User::ROLE_CUSTOMER);
        $bob = $this->makeUser(User::ROLE_CUSTOMER);
        $mine = $this->makeShipment($alice, $operator);
        $theirs = $this->makeShipment($bob, $operator);

        $this->actingAs($alice)->get('/reports')->assertOk()
            ->assertSee($mine->shipment_number)
            ->assertDontSee($theirs->shipment_number);
    }

    public function test_filters_narrow_the_report(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $ok = $this->makeShipment($customer, $operator);
        $late = $this->lateShipment($customer, $operator);

        $this->actingAs($admin)->get('/reports?delayed=1')->assertOk()
            ->assertSee($late->shipment_number)
            ->assertDontSee($ok->shipment_number);
    }

    public function test_invalid_filter_value_is_rejected(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->get('/reports?exception=bogus')->assertSessionHasErrors('exception');
    }

    public function test_summary_numbers_come_from_the_database(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $this->makeShipment($customer, $operator);
        $this->lateShipment($customer, $operator);

        $report = app(ReportService::class)->build($customer, []);

        $this->assertSame(2, $report['summary']['total']);
        $this->assertSame(1, $report['summary']['delayed']);
        $this->assertSame(50.0, $report['summary']['delay_rate_pct']);
        $this->assertCount(2, $report['rows']);
    }

    public function test_pdf_is_downloadable_when_the_engine_is_installed(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $this->lateShipment($this->makeUser(User::ROLE_CUSTOMER), $operator);

        $response = $this->actingAs($admin)->get('/reports/pdf');

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $response->assertSessionHas('error'); // friendly message instead of a crash

            return;
        }
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.downloaded']);
    }
}
