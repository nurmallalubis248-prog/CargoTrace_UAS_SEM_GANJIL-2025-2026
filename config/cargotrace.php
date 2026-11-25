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

    // Authorization codes required to self-register a privileged account from the login page
    // ("Register as" menu). An empty/missing code DISABLES registration for that role.
    'registration_codes' => [
        'admin' => env('ADMIN_REGISTRATION_CODE'),
        'operator' => env('OPERATOR_REGISTRATION_CODE'),
    ],

    // Freight fee (IDR) = weight_kg x rate_per_kg, at least min_fee.
    'payment' => [
        'rate_per_kg' => (int) env('CARGOTRACE_RATE_PER_KG', 5000),
        'min_fee' => (int) env('CARGOTRACE_MIN_FEE', 50000),
    ],

    // Display currencies. rate = units of currency per 1 IDR (static, approximate; update or replace with a live rates API).
    // Fees are calculated in IDR; other currencies are display-only (static rates).
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

    // Auto-shipping: a scheduled job (php artisan schedule:work) moves active shipments along their route by writing
    // real tracking events, until they arrive and are DELIVERED. See App\Services\SimulationService.
    // Production deployments receive positions from carriers/drivers via POST /api/shipments/{id}/tracking instead.
    'simulation' => [
        'enabled' => (bool) env('CARGOTRACE_SIMULATION', false),
        'step_percent' => (int) env('CARGOTRACE_SIMULATION_STEP', 10),           // only for mode 'step'
        'mode' => env('CARGOTRACE_SIMULATION_MODE', 'time'),                     // 'time' = follows the clock, 'step' = old N% per run
        'time_scale' => (float) env('CARGOTRACE_SIMULATION_TIME_SCALE', 1),      // 1 = real time; 144 = a 3-day trip takes ~30 min
        'auto_book_minutes' => env('CARGOTRACE_SIMULATION_AUTO_BOOK_MINUTES', 1), // minutes after creation -> BOOKED; null = off
        'auto_deliver' => (bool) env('CARGOTRACE_SIMULATION_AUTO_DELIVER', true), // ARRIVED -> DELIVERED without customer confirmation
        'speed_kmh' => (float) env('CARGOTRACE_SIMULATION_SPEED_KMH', 50),       // only when arrival time <= departure time
    ],
];
