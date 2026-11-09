<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Demo mode (CARGOTRACE_SIMULATION=true): move active shipments every minute. Run locally with: php artisan schedule:work
Schedule::command('cargotrace:simulate')->everyMinute()->withoutOverlapping()
    ->when(fn () => (bool) config('cargotrace.simulation.enabled'));
