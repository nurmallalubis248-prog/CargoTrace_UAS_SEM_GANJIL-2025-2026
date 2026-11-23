<?php

namespace App\Console\Commands;

use App\Enums\ShipmentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Shipment;
use App\Models\User;
use App\Services\SimulationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SimulateShipments extends Command
{
    protected $signature = 'cargotrace:simulate
        {--mode= : "time" (default: posisi mengikuti jam, otomatis sampai DELIVERED) atau "step" (mode lama: maju N% per run)}
        {--step= : mode step: persen rute per run (default dari CARGOTRACE_SIMULATION_STEP)}
        {--shipment= : hanya pengiriman dengan id ini}
        {--actor= : e-mail user pencatat event (default: operator pertama, lalu admin)}';

    protected $description = 'Jalankan pengiriman otomatis: pindahkan posisi kargo sesuai jadwal sampai tiba dan terkirim.';

    public function handle(SimulationService $simulation): int
    {
        // Tanda "scheduler hidup": dibaca halaman admin & cargotrace:diagnose untuk memberi peringatan bila berhenti.
        Cache::put('cargotrace.sim.heartbeat', now()->timestamp, now()->addDay());

        $mode = $this->option('mode') ?: config('cargotrace.simulation.mode', 'time');
        $actor = $this->option('actor')
            ? User::where('email', $this->option('actor'))->first()
            : (User::where('role', User::ROLE_OPERATOR)->first() ?? User::where('role', User::ROLE_ADMIN)->first());
        if (! $actor) {
            $this->error('No operator or admin user found to record the events as.');

            return self::FAILURE;
        }

        // Semua yang belum selesai; SimulationService yang memutuskan apa yang perlu dilakukan pada tiap status.
        $query = Shipment::query()
            ->whereNotIn('current_status', [ShipmentStatus::DELIVERED->value, ShipmentStatus::CANCELLED->value])
            ->with(['origin', 'destination', 'carrier']);
        if ($this->option('shipment')) {
            $query->whereKey($this->option('shipment'));
        }

        $events = 0;
        $touched = 0;
        foreach ($query->get() as $shipment) {
            try {
                if ($mode === 'step') {
                    $step = (int) ($this->option('step') ?: config('cargotrace.simulation.step_percent', 10));
                    $event = $simulation->advance($shipment, $actor, $step);
                    $n = $event ? 1 : 0;
                } else {
                    $n = $simulation->tick($shipment, $actor);
                }
            } catch (BusinessRuleException $e) {
                $this->warn("{$shipment->shipment_number}: {$e->getMessage()}");
                continue;
            }

            if ($n > 0) {
                $touched++;
                $events += $n;
                $this->line(sprintf('%s: %d event(s) -> %s', $shipment->shipment_number, $n, $shipment->fresh()->current_status->value));
            }
        }
        $this->info("Updated {$touched} shipment(s), {$events} event(s) written (mode: {$mode}).");

        return self::SUCCESS;
    }
}
