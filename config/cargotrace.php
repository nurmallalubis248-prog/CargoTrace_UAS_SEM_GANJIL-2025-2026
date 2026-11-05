<?php

return [
    /*
    | Delay classification (deterministic business rule, see App\Services\EtaService).
    |   delay <= 0                  => not delayed
    |   0 < delay <= low_max_hours  => LOW
    |   low_max < delay <= medium_max_hours => MEDIUM
    |   delay > medium_max_hours    => HIGH
    | Change these via .env to give each scenario/group a different threshold.
    */
    'delay' => [
        'low_max_hours' => (float) env('CARGOTRACE_DELAY_LOW_MAX_HOURS', 6),
        'medium_max_hours' => (float) env('CARGOTRACE_DELAY_MEDIUM_MAX_HOURS', 24),
    ],

    // Shows the demo accounts on the login page (set SHOW_DEMO_ACCOUNTS=true on the demo deployment).
    'show_demo_accounts' => (bool) env('SHOW_DEMO_ACCOUNTS', false),

    // Freight fee (IDR) = weight_kg x rate_per_kg, at least min_fee.
    'payment' => [
        'rate_per_kg' => (int) env('CARGOTRACE_RATE_PER_KG', 5000),
        'min_fee' => (int) env('CARGOTRACE_MIN_FEE', 50000),
    ],

    // Display currencies. rate = units of currency per 1 IDR (static, approximate; update or replace with a live rates API).
    // Payments are always charged in IDR through Midtrans.
    'currencies' => [
        'IDR' => ['rate' => 1, 'locale' => 'id-ID'],
        'USD' => ['rate' => 0.0000602, 'locale' => 'en-US'],
        'EUR' => ['rate' => 0.0000557, 'locale' => 'de-DE'],
        'CNY' => ['rate' => 0.000434, 'locale' => 'zh-CN'],
        'JPY' => ['rate' => 0.0092, 'locale' => 'ja-JP'],
        'SGD' => ['rate' => 0.0000807, 'locale' => 'en-SG'],
        'MYR' => ['rate' => 0.000265, 'locale' => 'ms-MY'],
        'SAR' => ['rate' => 0.000226, 'locale' => 'ar-SA'],
    ],

    'cargo_types' => [
        'GENERAL' => 'General cargo',
        'CONTAINER_FCL' => 'Container (FCL)',
        'CONTAINER_LCL' => 'Container (LCL)',
        'BULK' => 'Bulk',
        'REFRIGERATED' => 'Refrigerated',
        'LIQUID' => 'Liquid / tanker',
        'HAZARDOUS' => 'Hazardous',
    ],
];
