<?php

namespace Tests\Unit;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Services\EtaService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EtaServiceTest extends TestCase
{
    #[DataProvider('hoursProvider')]
    public function test_classify_applies_default_thresholds(float $hours, bool $delayed, ?string $severity): void
    {
        $result = (new EtaService())->classify($hours);

        $this->assertSame($delayed, $result['is_delayed']);
        $this->assertSame($severity, $result['severity']);
    }

    public static function hoursProvider(): array
    {
        return [
            'early' => [-3.0, false, null],
            'exactly on time' => [0.0, false, null],
            'tiny delay' => [0.5, true, 'LOW'],
            'upper LOW bound' => [6.0, true, 'LOW'],
            'just above LOW' => [6.01, true, 'MEDIUM'],
            'upper MEDIUM bound' => [24.0, true, 'MEDIUM'],
            'just above MEDIUM' => [24.01, true, 'HIGH'],
            'very late' => [100.0, true, 'HIGH'],
        ];
    }

    public function test_thresholds_are_configurable(): void
    {
        config(['cargotrace.delay.low_max_hours' => 12, 'cargotrace.delay.medium_max_hours' => 48]);

        $eta = new EtaService();
        $this->assertSame('LOW', $eta->classify(10)['severity']);
        $this->assertSame('MEDIUM', $eta->classify(30)['severity']);
        $this->assertSame('HIGH', $eta->classify(49)['severity']);
    }

    public function test_evaluate_uses_eta_while_in_transit_and_explains_the_values(): void
    {
        $shipment = new Shipment();
        $shipment->current_status = ShipmentStatus::IN_TRANSIT;
        $shipment->expected_arrival_at = '2026-12-01 12:00:00';
        $shipment->estimated_arrival_at = '2026-12-02 12:00:00'; // 24 h late

        $result = (new EtaService())->evaluate($shipment);

        $this->assertTrue($result['is_delayed']);
        $this->assertSame(24.0, $result['delay_hours']);
        $this->assertSame('MEDIUM', $result['severity']);
        $this->assertStringContainsString('2026-12-02 12:00', $result['reason']);
        $this->assertStringContainsString('2026-12-01 12:00', $result['reason']);
    }

    public function test_evaluate_uses_actual_arrival_when_delivered_and_ignores_cancelled(): void
    {
        $shipment = new Shipment();
        $shipment->current_status = ShipmentStatus::DELIVERED;
        $shipment->expected_arrival_at = '2026-12-01 12:00:00';
        $shipment->estimated_arrival_at = '2026-12-01 12:00:00';
        $shipment->actual_arrival_at = '2026-12-01 20:00:00';

        $result = (new EtaService())->evaluate($shipment);
        $this->assertSame('MEDIUM', $result['severity']);
        $this->assertSame(8.0, $result['delay_hours']);

        $shipment->current_status = ShipmentStatus::CANCELLED;
        $this->assertFalse((new EtaService())->evaluate($shipment)['is_delayed']);
    }
}
