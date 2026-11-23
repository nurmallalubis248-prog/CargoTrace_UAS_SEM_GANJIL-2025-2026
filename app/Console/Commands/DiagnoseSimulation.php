<?php

namespace App\Console\Commands;

use App\Enums\ShipmentStatus;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\RouteGeometry;
use App\Services\ShipmentService;
use App\Services\SimulationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DiagnoseSimulation extends Command
{
    protected $signature = 'cargotrace:diagnose
        {--selftest : Uji perjalanan lengkap pada data sementara (otomatis dibatalkan/rollback, data aslimu tidak berubah)}';

    protected $description = 'Periksa apakah pengiriman otomatis benar-benar berjalan dan sampai tujuan.';

    public function handle(SimulationService $simulation, ShipmentService $shipments): int
    {
        $cfg = (array) config('cargotrace.simulation');
        $enabled = (bool) ($cfg['enabled'] ?? false);
        $scale = max(1.0, (float) ($cfg['time_scale'] ?? 1));
        $problems = 0;

        $this->info('== 1. Pengaturan ==');
        $this->table(['Pengaturan', 'Nilai'], [
            ['CARGOTRACE_SIMULATION', $enabled ? 'true (aktif)' : 'false (MATI: pengiriman TIDAK bergerak sendiri)'],
            ['mode', (string) ($cfg['mode'] ?? 'time')],
            ['time_scale', $scale.'x'.($scale > 1 ? ' (waktu dipercepat)' : ' (waktu nyata)')],
            ['auto_book_minutes', var_export($cfg['auto_book_minutes'] ?? null, true)],
            ['auto_deliver', ! empty($cfg['auto_deliver']) ? 'true (ARRIVED langsung DELIVERED)' : 'false (menunggu konfirmasi customer)'],
            ['APP_TIMEZONE', (string) config('app.timezone')],
            ['Waktu server (UTC)', now()->utc()->format('Y-m-d H:i:s')],
        ]);
        if (! $enabled) {
            $problems++;
            $this->warn('Simulasi mati. Isi CARGOTRACE_SIMULATION=true di .env, lalu: php artisan config:clear');
        }

        $this->info('== 2. Penggerak simulasi ==');
        $beat = Cache::get('cargotrace.sim.heartbeat');
        if (! $beat) {
            $this->line('Belum pernah berjalan. Buka halaman mana pun di aplikasi (otomatis mengejar jadwal), atau jalankan: php artisan cargotrace:simulate');
        } else {
            $age = max(0, now()->timestamp - (int) $beat);
            $this->line("Terakhir berjalan {$age} detik lalu (oleh scheduler atau saat ada yang membuka aplikasi).");
            if ($age > 180) {
                $this->line('   Tidak ada yang membuka aplikasi / scheduler mati. Itu tidak masalah: begitu aplikasi dibuka, pengiriman otomatis mengejar jadwal.');
            }
        }
        $err = Cache::get('cargotrace.sim.error');
        if ($err) {
            $problems++;
            $this->error('Galat terakhir saat mengejar jadwal: '.$err);
        }

        $this->info('== 3. Data pengiriman ==');
        $counts = Shipment::query()->selectRaw('current_status as s, count(*) as c')->groupBy('current_status')->pluck('c', 's');
        $rows = [];
        foreach (ShipmentStatus::cases() as $st) {
            $rows[] = [$st->value, (int) ($counts[$st->value] ?? 0)];
        }
        $this->table(['Status', 'Jumlah'], $rows);

        $active = Shipment::query()
            ->whereNotIn('current_status', [ShipmentStatus::DELIVERED->value, ShipmentStatus::CANCELLED->value])
            ->limit(500)->get();
        $now = now();
        $late = [];
        foreach ($active as $s) {
            $dep = $s->expected_departure_at;
            $arr = $dep->copy()->addSeconds((int) round(max(0, $s->expected_arrival_at->getTimestamp() - $dep->getTimestamp()) / $scale));
            $why = null;
            if ($s->current_status === ShipmentStatus::CREATED && $s->created_at->lt($now->copy()->subMinutes(((float) ($cfg['auto_book_minutes'] ?? 1)) + 3))) {
                $why = 'masih CREATED padahal sudah lewat waktu auto-book';
            } elseif ($s->current_status === ShipmentStatus::BOOKED && $dep->lt($now->copy()->subMinutes(3))) {
                $why = 'masih BOOKED padahal jadwal berangkat sudah lewat';
            } elseif (in_array($s->current_status, [ShipmentStatus::PICKED_UP, ShipmentStatus::IN_TRANSIT], true) && $arr->lt($now->copy()->subMinutes(3))) {
                $why = 'masih di jalan padahal jadwal tiba sudah lewat';
            } elseif ($s->current_status === ShipmentStatus::ARRIVED && SimulationService::autoDeliver()) {
                $why = 'sudah ARRIVED tapi belum DELIVERED';
            }
            if ($why) {
                $late[] = [$s->shipment_number, $s->current_status->value, $dep->format('Y-m-d H:i'), $arr->format('Y-m-d H:i'), $why];
            }
        }
        if ($late) {
            $this->warn(count($late).' pengiriman tertinggal dari jadwalnya (seharusnya sudah maju):');
            $this->table(['Nomor', 'Status', 'Berangkat (UTC)', 'Tiba (UTC)', 'Masalah'], array_slice($late, 0, 15));
            $this->line('   Akan terkejar otomatis saat aplikasi dibuka berikutnya. Untuk mengejar sekarang: php artisan cargotrace:simulate');
        } else {
            $this->line('OK, tidak ada pengiriman aktif yang tertinggal dari jadwalnya.');
        }

        if ($this->option('selftest')) {
            $this->newLine();
            $this->info('== 4. Uji perjalanan lengkap (data sementara, otomatis dibatalkan) ==');
            if (! $this->selfTest($simulation, $shipments)) {
                $problems++;
            }
        } else {
            $this->newLine();
            $this->line('Tambahkan --selftest untuk menguji perjalanan penuh: php artisan cargotrace:diagnose --selftest');
        }

        $this->newLine();
        if ($problems === 0) {
            $this->info('KESIMPULAN: tidak ditemukan masalah.');

            return self::SUCCESS;
        }
        $this->error("KESIMPULAN: ada {$problems} hal yang perlu diperbaiki (lihat peringatan di atas).");

        return self::FAILURE;
    }

    private function selfTest(SimulationService $simulation, ShipmentService $shipments): bool
    {
        $actor = User::where('role', User::ROLE_OPERATOR)->first() ?? User::where('role', User::ROLE_ADMIN)->first();
        $customer = User::where('role', User::ROLE_CUSTOMER)->first();
        $carrier = Carrier::where('is_active', true)->first();
        $locations = Location::where('is_active', true)->get();
        if (! $actor || ! $customer || ! $carrier || $locations->count() < 2) {
            $this->error('Data dasar belum lengkap (butuh operator/admin, customer, pengangkut aktif, dan 2 lokasi aktif).');

            return false;
        }

        if (config('database.default') === 'mysql') {
            $engine = DB::selectOne("select engine as e from information_schema.tables where table_schema = database() and table_name = 'shipments'");
            if ($engine && strtolower((string) $engine->e) !== 'innodb') {
                $this->error('Tabel shipments bukan InnoDB, rollback tidak aman. Uji dibatalkan supaya datamu tidak tercampur.');

                return false;
            }
        }

        // Pilih dua lokasi yang berjauhan (>30 km) agar posisi di tengah jalan bisa dibedakan.
        $a = $locations[0];
        $b = $locations[1];
        foreach ($locations as $x) {
            foreach ($locations as $y) {
                if ($x->id !== $y->id && RouteGeometry::distance([(float) $x->latitude, (float) $x->longitude], [(float) $y->latitude, (float) $y->longitude]) > 30000) {
                    $a = $x;
                    $b = $y;
                    break 2;
                }
            }
        }

        $original = config('cargotrace.simulation');
        config(['cargotrace.simulation.time_scale' => 1, 'cargotrace.simulation.auto_book_minutes' => 1]);
        $ok = true;

        $make = function (Carbon $dep, Carbon $arr, Carbon $createdAt) use ($shipments, $actor, $customer, $carrier, $a, $b): Shipment {
            $s = $shipments->create([
                'reference_number' => 'SELFTEST',
                'customer_id' => $customer->id,
                'carrier_id' => $carrier->id,
                'origin_id' => $a->id,
                'destination_id' => $b->id,
                'cargo_type' => 'GENERAL',
                'weight_kg' => 10,
                'expected_departure_at' => $dep,
                'expected_arrival_at' => $arr,
                'items' => [['description' => 'Self-test item', 'quantity' => 1]],
            ], $actor, $createdAt);
            DB::table('shipments')->where('id', $s->id)->update(['created_at' => $createdAt]);

            return $s->fresh();
        };
        $run = function (Shipment $s) use ($simulation, $actor): Shipment {
            for ($i = 0; $i < 12; $i++) {
                if ($simulation->tick($s, $actor) === 0) {
                    break;
                }
            }

            return $s->fresh();
        };
        $report = function (bool $pass, string $text) use (&$ok): void {
            $ok = $ok && $pass;
            $this->line(sprintf('[%s] %s', $pass ? 'LULUS' : 'GAGAL', $text));
        };

        DB::beginTransaction();
        try {
            $this->line("Rute uji: {$a->name} -> {$b->name} (pengangkut {$carrier->name})");

            // 1) Jadwal sudah lewat: harus berjalan penuh sampai tujuan.
            $arrival = now()->subHour();
            $s1 = $run($make(now()->subHours(3), $arrival, now()->subHours(4)));
            $final = SimulationService::autoDeliver() ? ShipmentStatus::DELIVERED : ShipmentStatus::ARRIVED;
            $report($s1->current_status === $final, "Jadwal sudah lewat -> status akhir {$s1->current_status->value} (diharapkan {$final->value})");
            $events1 = $s1->trackingEvents()->orderBy('occurred_at')->orderBy('id')->get();
            $steps = $events1->filter(fn ($e) => $e->status !== null)
                ->map(fn ($e) => $e->status->value.'@'.$e->occurred_at->format('H:i'))->implode(' > ');
            $cp1 = $events1->where('event_type', TrackingEvent::TYPE_CHECKPOINT)->count();
            $this->line('   Alur: '.$steps.' (+'.$cp1.' titik posisi)');
            $report($cp1 >= 10, "Riwayat perjalanan terisi walau tidak ada yang menjalankan aplikasi selama jadwal berlangsung ({$cp1} titik posisi)");
            $report($s1->actual_arrival_at !== null && abs($s1->actual_arrival_at->diffInMinutes($arrival)) <= 5,
                'Waktu tiba tercatat sesuai jadwal (bukan jam scheduler dijalankan)');

            // 2) Sedang di jalan: harus IN_TRANSIT dengan posisi di antara asal dan tujuan.
            $s2 = $run($make(now()->subHour(), now()->addHour(), now()->subHours(2)));
            $here = [(float) $s2->current_latitude, (float) $s2->current_longitude];
            $d1 = RouteGeometry::distance([(float) $a->latitude, (float) $a->longitude], $here);
            $d2 = RouteGeometry::distance($here, [(float) $b->latitude, (float) $b->longitude]);
            $checkpoints = $s2->trackingEvents()->where('event_type', TrackingEvent::TYPE_CHECKPOINT)->count();
            $report($s2->current_status === ShipmentStatus::IN_TRANSIT && $d1 > 500 && $d2 > 500 && $checkpoints >= 1,
                sprintf('Di tengah perjalanan -> %s, %.0f km dari asal, %.0f km menuju tujuan, %d titik posisi tercatat',
                    $s2->current_status->value, $d1 / 1000, $d2 / 1000, $checkpoints));

            // 3) Jadwal berangkat masih di depan: tidak boleh berangkat lebih awal.
            $s3 = $run($make(now()->addHours(2), now()->addHours(5), now()->subHours(2)));
            $report($s3->current_status === ShipmentStatus::BOOKED, "Belum waktunya berangkat -> {$s3->current_status->value} (diharapkan BOOKED)");

            // 4) Kejar jadwal setelah 'laptop mati' (jalur yang sama dengan membuka halaman mana pun) + refresh tidak mengubah apa pun.
            config(['cargotrace.simulation.enabled' => true, 'cargotrace.simulation.mode' => 'time']);
            $s4 = $make(now()->subHours(10), now()->addHours(10), now()->subHours(11));
            $simulation->catchUp($s4);
            $s4 = $s4->fresh();
            $p1 = [(float) $s4->current_latitude, (float) $s4->current_longitude];
            $e1 = $s4->trackingEvents()->count();
            $f1 = RouteGeometry::distance([(float) $a->latitude, (float) $a->longitude], $p1);
            $f2 = RouteGeometry::distance($p1, [(float) $b->latitude, (float) $b->longitude]);
            $frac = ($f1 + $f2) > 0 ? $f1 / ($f1 + $f2) : 0;
            $report($s4->current_status === ShipmentStatus::IN_TRANSIT && $frac > 0.25 && $frac < 0.75 && $e1 >= 8,
                sprintf('Dibuka setelah 11 jam mati -> %s di sekitar %.0f%% rute dengan %d event (diharapkan sekitar 50%%)', $s4->current_status->value, $frac * 100, $e1));
            $simulation->catchUp($s4);
            $s4 = $s4->fresh();
            $p2 = [(float) $s4->current_latitude, (float) $s4->current_longitude];
            $report(RouteGeometry::distance($p1, $p2) < 2000 && $s4->trackingEvents()->count() <= $e1 + 1,
                'Di-refresh/dibuka lagi: posisi tetap, tidak mengulang dari awal dan tidak menambah riwayat berlebihan');
        } catch (\Throwable $e) {
            $ok = false;
            $this->error('Uji berhenti karena error: '.$e->getMessage());
        } finally {
            DB::rollBack();
            config(['cargotrace.simulation' => $original]);
            $this->line('(Data uji dibatalkan; datamu tidak berubah.)');
        }

        return $ok;
    }
}
