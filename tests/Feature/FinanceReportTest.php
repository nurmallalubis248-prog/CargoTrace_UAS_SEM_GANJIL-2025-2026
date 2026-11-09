<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\FinanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class FinanceReportTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function payment(array $attrs = []): Payment
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);
        $customer = $this->makeUser(User::ROLE_CUSTOMER);
        $shipment = $this->makeShipment($customer, $operator);
        $p = Payment::create(array_merge([
            'shipment_id' => $shipment->id, 'user_id' => $customer->id, 'order_id' => 'CT-FIN-'.uniqid(),
            'amount' => 100000, 'status' => 'PAID', 'payment_type' => 'qris',
        ], array_diff_key($attrs, array_flip(['paid_at', 'payout_status', 'settled_at']))));
        $p->forceFill(array_intersect_key($attrs, array_flip(['paid_at', 'payout_status', 'settled_at'])) + ['paid_at' => $p->status === 'PAID' ? now() : null])->save();

        return $p->fresh();
    }

    public function test_totals_split_revenue_into_pending_and_settled_and_ignore_unpaid(): void
    {
        $this->payment(['amount' => 100000]);
        $this->payment(['amount' => 50000, 'payout_status' => 'SETTLED', 'settled_at' => now()]);
        $this->payment(['amount' => 70000, 'status' => 'PENDING']);

        $r = app(FinanceReportService::class)->build();

        $this->assertSame(150000, $r['received']);
        $this->assertSame(100000, $r['pending_settlement']);
        $this->assertSame(50000, $r['settled']);
        $this->assertSame(2, $r['count']);
    }

    public function test_period_and_payout_filters(): void
    {
        $this->payment(['amount' => 100000, 'paid_at' => now()->subDays(40)]);
        $this->payment(['amount' => 200000]);
        $this->payment(['amount' => 300000, 'payout_status' => 'SETTLED', 'settled_at' => now()]);

        $service = app(FinanceReportService::class);

        $this->assertSame(500000, $service->build(now()->subDays(7)->format('Y-m-d'), null, null)['received']);
        $this->assertSame(300000, $service->build(null, null, 'SETTLED')['received']);
        $this->assertSame(100000, $service->build(null, now()->subDays(30)->format('Y-m-d'), null)['received']);
    }

    public function test_only_admins_see_the_accounting_page_and_can_settle(): void
    {
        $payment = $this->payment();
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($this->makeUser(User::ROLE_CUSTOMER))->get('/admin/accounting')->assertForbidden();
        $this->actingAs($this->makeUser(User::ROLE_OPERATOR))->post("/admin/accounting/{$payment->id}/settle")->assertForbidden();

        $this->actingAs($admin)->get('/admin/accounting')->assertOk()->assertSee($payment->order_id);
        $this->actingAs($admin)->post("/admin/accounting/{$payment->id}/settle")->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame('SETTLED', $payment->payout_status);
        $this->assertNotNull($payment->settled_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.settled']);
    }

    public function test_an_unpaid_payment_cannot_be_settled(): void
    {
        $payment = $this->payment(['status' => 'PENDING']);

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))->post("/admin/accounting/{$payment->id}/settle")->assertStatus(422);
        $this->assertSame('PENDING_SETTLEMENT', $payment->fresh()->payout_status);
    }

    public function test_finance_pdf_rules(): void
    {
        $this->payment();
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($this->makeUser(User::ROLE_CUSTOMER))->get('/admin/accounting/pdf')->assertForbidden();
        $this->actingAs($admin)->get('/admin/accounting/pdf?payout=bogus')->assertSessionHasErrors('payout');

        $response = $this->actingAs($admin)->get('/admin/accounting/pdf?lang=id');
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $response->assertSessionHas('error');

            return;
        }
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_finance_pdf_view_follows_the_selected_language(): void
    {
        $this->payment();
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $data = ['report' => app(FinanceReportService::class)->build(), 'user' => $admin];

        app()->setLocale('id');
        $id = view('admin.finance-pdf', $data)->render();
        app()->setLocale('en');
        $en = view('admin.finance-pdf', $data)->render();

        $this->assertStringContainsString('Laporan keuangan', $id);
        $this->assertStringContainsString('Menunggu pencairan', $id);
        $this->assertStringContainsString('Financial report', $en);
        $this->assertStringContainsString('Pending settlement', $en);
    }
}
