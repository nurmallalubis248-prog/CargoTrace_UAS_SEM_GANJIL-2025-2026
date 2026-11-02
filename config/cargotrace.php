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
