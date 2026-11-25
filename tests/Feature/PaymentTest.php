<?php

namespace Tests\Feature;

use App\Http\Controllers\Web\PaymentController;
use App\Models\Shipment;
use Tests\TestCase;

/** Freight fee calculation. There is no online payment gateway in this project. */
class PaymentTest extends TestCase
{
    public function test_fee_is_weight_times_rate_with_a_minimum(): void
    {
        config(['cargotrace.payment.rate_per_kg' => 5000, 'cargotrace.payment.min_fee' => 50000]);

        $heavy = new Shipment();
        $heavy->weight_kg = 1000;
        $light = new Shipment();
        $light->weight_kg = 1;

        $this->assertSame(5000000, PaymentController::amountFor($heavy));
        $this->assertSame(50000, PaymentController::amountFor($light));
    }
}
