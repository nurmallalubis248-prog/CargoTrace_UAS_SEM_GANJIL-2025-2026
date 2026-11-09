<?php

namespace App\Console\Commands;

use App\Enums\ShipmentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Shipment;
use App\Models\User;
use App\Services\SimulationService;
use Illuminate\Console\Command;

class SimulateShipments extends Command
{
    protected $signature = 'cargotrace:simulate
        {--step= : percent of the route to move per run (default from CARGOTRACE_SIMULATION_STEP)}
        {--shipment= : only this shipment id}
        {--actor= : e-mail of the user the events are recorded as (default: first operator, then admin)}';

    protected $description = 'Demo mode: move active shipments along their route by recording tracking events.';

    public function handle(SimulationService $simulation): int
    {
        $step = (int) ($this->option('step') ?: config('cargotrace.simulation.step_percent', 10));
        $actor = $this->option('actor')
            ? User::where('email', $this->option('actor'))->first()
            : (User::where('role', User::ROLE_OPERATOR)->first() ?? User::where('role', User::ROLE_ADMIN)->first());
        if (! $actor) {
            $this->error('No operator or admin user found to record the events as.');

            return self::FAILURE;
        }

        $query = Shipment::query()
            ->whereIn('current_status', [ShipmentStatus::BOOKED->value, ShipmentStatus::PICKED_UP->value, ShipmentStatus::IN_TRANSIT->value])
            ->with(['origin', 'destination', 'carrier']);
        if ($this->option('shipment')) {
            $query->whereKey($this->option('shipment'));
        }

        $moved = 0;
        foreach ($query->get() as $shipment) {
            try {
                $event = $simulation->advance($shipment, $actor, $step);
                if ($event) {
                    $moved++;
                    $this->line(sprintf('%s: %s %s', $shipment->shipment_number, $event->status ?? $event->event_type, $event->location_label));
                }
            } catch (BusinessRuleException $e) {
                $this->warn("{$shipment->shipment_number}: {$e->getMessage()}");
            }
        }
        $this->info("Advanced {$moved} shipment(s).");

        return self::SUCCESS;
    }
}
