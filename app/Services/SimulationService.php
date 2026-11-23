<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Pengiriman otomatis berbasis WAKTU (mode "time", default).
 *
 * Posisi kargo dihitung dari jam: progres = (sekarang - berangkat) / (tiba - berangkat), lalu titik itu dicari
 * sepanjang rute jalan asli (OSRM). Setiap posisi ditulis sebagai TrackingEvent sungguhan lewat TrackingService
 * (aturan bisnis, notifikasi, dan audit log sama seperti operator manusia), jadi tersimpan di database dan peta
 * hanya membaca dari sana.
 *
 * Alur penuh, tanpa campur tangan siapa pun:
 *   CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> (checkpoint posisi) -> ARRIVED -> DELIVERED
 *
 * Mode "step" (lama) tetap ada: maju N% tiap dijalankan dan berhenti di ARRIVED.
 * Semua event diberi label "[Simulation]".
 *
 * Konfigurasi (config/cargotrace.php -> 'simulation'), semuanya opsional karena ada nilai bawaan:
 *   mode               'time' | 'step'          bawaan 'time'
 *   time_scale         pengali waktu demo       bawaan 1 (waktu nyata). 144 = perjalanan 3 hari selesai ~30 menit
 *   auto_book_minutes  menit setelah dibuat sampai otomatis BOOKED; null/negatif = jangan auto-book   bawaan 1
 *   auto_deliver       ARRIVED langsung DELIVERED tanpa konfirmasi customer                           bawaan true
 *   speed_kmh          kecepatan bila jadwal tiba <= jadwal berangkat                                  bawaan 50
 */
class SimulationService
{
    public function __construct(private TrackingService $tracking, private RouteGeometry $routes)
    {
    }

    /** True bila tiba di tujuan langsung dianggap terkirim (tanpa pembayaran/konfirmasi customer). */
    public static function autoDeliver(): bool
    {
        return (bool) config('cargotrace.simulation.enabled')
            && (bool) config('cargotrace.simulation.auto_deliver', true);
    }

    /**
     * Jadwal berangkat/tiba (milidetik, sudah dibagi time_scale) untuk peta di browser, atau null bila simulasi mati.
     * Posisi kendaraan di peta dihitung dari jadwal + jam nyata, jadi sama persis setiap kali halaman dibuka/di-refresh.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function windowMs(Shipment $shipment): ?array
    {
        if (! config('cargotrace.simulation.enabled') || config('cargotrace.simulation.mode', 'time') !== 'time') {
            return null;
        }
        $dep = $shipment->expected_departure_at;
        $arr = $shipment->expected_arrival_at;
        if (! $dep || ! $arr) {
            return null;
        }
        $seconds = $arr->getTimestamp() - $dep->getTimestamp();
        if ($seconds <= 0) {
            return null;
        }
        $scale = max(1.0, (float) config('cargotrace.simulation.time_scale', 1));

        return [$dep->getTimestamp() * 1000, ($dep->getTimestamp() + (int) round($seconds / $scale)) * 1000];
    }

    /**
     * Kejar jadwal: bawa pengiriman aktif ke keadaan yang SEHARUSNYA pada jam sekarang. Dipanggil dari mana pun
     * (scheduler, setiap halaman yang dibuka, halaman detail) dan aman diulang. Karena posisi berasal dari jam, bukan
     * dari proses yang harus terus menyala, laptop/aplikasi boleh mati: begitu dibuka lagi, perjalanan yang terlewat
     * terisi lengkap dengan waktu sesuai jadwal (bukan mengulang dari awal).
     *
     * @return int jumlah event yang ditulis
     */
    public function catchUp(?Shipment $only = null): int
    {
        if (! config('cargotrace.simulation.enabled') || config('cargotrace.simulation.mode', 'time') !== 'time') {
            return 0;
        }
        $actor = User::where('role', User::ROLE_OPERATOR)->first() ?? User::where('role', User::ROLE_ADMIN)->first();
        if (! $actor) {
            return 0;
        }

        $lock = Cache::lock('cargotrace.sim.run', 120);
        if (! $lock->get()) {
            return 0;   // proses lain sedang mengejar jadwal
        }

        try {
            Cache::put('cargotrace.sim.heartbeat', now()->timestamp, now()->addDay());
            $query = Shipment::query()
                ->whereNotIn('current_status', [ShipmentStatus::DELIVERED->value, ShipmentStatus::CANCELLED->value])
                ->with(['origin', 'destination', 'carrier']);
            if ($only) {
                $query->whereKey($only->id);
            }

            $written = 0;
            foreach ($query->limit(300)->get() as $shipment) {
                try {
                    $written += $this->tick($shipment, $actor);
                } catch (BusinessRuleException) {
                    continue;
                }
            }
            Cache::forget('cargotrace.sim.error');

            return $written;
        } catch (\Throwable $e) {
            Cache::put('cargotrace.sim.error', $e->getMessage(), now()->addHour());
            report($e);

            return 0;
        } finally {
            $lock->release();
        }
    }

    /**
     * Mode waktu: bawa pengiriman sejauh yang seharusnya sudah ditempuh saat ini.
     * Aman dijalankan berulang; hanya menulis event bila ada perubahan.
     *
     * @return int jumlah event yang ditulis
     */
    public function tick(Shipment $shipment, User $actor): int
    {
        $written = 0;

        // Satu putaran bisa melewati beberapa status sekaligus (mis. BOOKED -> PICKED_UP -> IN_TRANSIT).
        for ($i = 0; $i < 8; $i++) {
            $shipment = $shipment->fresh(['origin', 'destination', 'carrier']);
            if (! $shipment || ! $shipment->origin || ! $shipment->destination) {
                break;
            }

            [$departure, $arrival] = $this->schedule($shipment);
            $now = now();

            switch ($shipment->current_status) {
                case ShipmentStatus::CREATED:
                    $minutes = config('cargotrace.simulation.auto_book_minutes', 1);
                    if ($minutes === null || (float) $minutes < 0 || $now->lt($shipment->created_at->copy()->addMinutes((float) $minutes))) {
                        break 2;
                    }
                    $bookedAt = $this->at($shipment, $shipment->created_at->copy()->addMinutes((float) $minutes));
                    $this->status($shipment, $actor, ShipmentStatus::BOOKED, $shipment->origin, 'Shipment booked automatically.', $bookedAt, $arrival);
                    break;

                case ShipmentStatus::BOOKED:
                    if ($now->lt($departure)) {
                        break 2;   // belum waktunya berangkat
                    }
                    $this->status($shipment, $actor, ShipmentStatus::PICKED_UP, $shipment->origin, 'Cargo picked up at origin.', $this->at($shipment, $departure), $arrival);
                    break;

                case ShipmentStatus::PICKED_UP:
                    $this->status($shipment, $actor, ShipmentStatus::IN_TRANSIT, $shipment->origin, 'Departed, now in transit.', $this->at($shipment, $departure), $arrival);
                    break;

                case ShipmentStatus::IN_TRANSIT:
                    $progress = $this->progress($departure, $arrival, $now);
                    // Aplikasi tidak dibuka sebentar/lama: isi jejak yang terlewat (titik posisi tiap 5% rute, di waktu jadwalnya).
                    $written += $this->backfill($shipment, $actor, $departure, $arrival, $progress);
                    if ($progress >= 1.0) {
                        // Waktu tiba = jadwal tiba (bukan jam scheduler jalan), supaya tidak dianggap telat beberapa detik.
                        $this->status($shipment, $actor, ShipmentStatus::ARRIVED, $shipment->destination,
                            'Arrived at destination.', $this->at($shipment, $arrival), $arrival);
                        break;
                    }
                    if ($this->checkpoint($shipment, $actor, $progress, $arrival)) {
                        $written++;
                    }
                    break 2;   // masih di jalan: selesai untuk putaran ini

                case ShipmentStatus::ARRIVED:
                    if (! self::autoDeliver()) {
                        break 2;   // menunggu customer bayar + konfirmasi
                    }
                    $this->status($shipment, $actor, ShipmentStatus::DELIVERED, $shipment->destination,
                        'Delivered to destination.', $this->at($shipment, $arrival), $arrival, system: true);
                    break;

                default:
                    break 2;   // DELIVERED / CANCELLED
            }
            $written++;
        }

        return $written;
    }

    /**
     * Mode lama (langkah persen). Satu transisi atau satu langkah per panggilan; berhenti di ARRIVED.
     */
    public function advance(Shipment $shipment, User $actor, int $stepPercent): ?TrackingEvent
    {
        $shipment->loadMissing(['origin', 'destination', 'carrier']);
        if (! $shipment->origin || ! $shipment->destination) {
            return null;
        }
        $stepPercent = max(1, min(100, $stepPercent));

        return match ($shipment->current_status) {
            ShipmentStatus::BOOKED => $this->status($shipment, $actor, ShipmentStatus::PICKED_UP, $shipment->origin, 'Cargo picked up at origin.'),
            ShipmentStatus::PICKED_UP => $this->status($shipment, $actor, ShipmentStatus::IN_TRANSIT, $shipment->origin, 'Departed, now in transit.'),
            ShipmentStatus::IN_TRANSIT => $this->move($shipment, $actor, $stepPercent),
            ShipmentStatus::ARRIVED => self::autoDeliver()
                ? $this->status($shipment, $actor, ShipmentStatus::DELIVERED, $shipment->destination, 'Delivered to destination.', system: true)
                : null,
            default => null,
        };
    }

    // ---- waktu -----------------------------------------------------------------

    /**
     * Jadwal berangkat dan tiba dalam jam NYATA (sudah dibagi time_scale).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function schedule(Shipment $shipment): array
    {
        $departure = $shipment->expected_departure_at->copy();
        $arrival = $shipment->expected_arrival_at->copy();
        $seconds = $arrival->getTimestamp() - $departure->getTimestamp();

        if ($seconds <= 0) {
            // Jadwal tidak masuk akal: pakai jarak rute / kecepatan.
            $path = $this->routes->path($shipment);
            $metres = 0.0;
            for ($i = 1; $i < count($path); $i++) {
                $metres += RouteGeometry::distance($path[$i - 1], $path[$i]);
            }
            $seconds = (int) max(600, round($metres / 1000 / max(1, (float) config('cargotrace.simulation.speed_kmh', 50)) * 3600));
        }

        $scale = max(1.0, (float) config('cargotrace.simulation.time_scale', 1));

        return [$departure, $departure->copy()->addSeconds((int) round($seconds / $scale))];
    }

    private function progress(Carbon $departure, Carbon $arrival, Carbon $now): float
    {
        $total = max(1, $arrival->getTimestamp() - $departure->getTimestamp());

        return max(0.0, min(1.0, ($now->getTimestamp() - $departure->getTimestamp()) / $total));
    }

    /** Waktu kejadian: jadwalnya, tapi tidak boleh lebih awal dari event terakhir dan tidak di masa depan. */
    private function at(Shipment $shipment, Carbon $planned): Carbon
    {
        $t = $planned->copy();
        if ($shipment->last_event_at && $t->lt($shipment->last_event_at)) {
            $t = $shipment->last_event_at->copy();
        }

        return $t->gt(now()) ? now() : $t;
    }

    // ---- penulisan event --------------------------------------------------------

    /** Titik posisi yang terlewat antara event terakhir dan sekarang: satu per 5% rute, bertanda waktu sesuai jadwal. */
    private function backfill(Shipment $shipment, User $actor, Carbon $departure, Carbon $arrival, float $progress): int
    {
        $step = 0.05;
        $lastTs = $shipment->last_event_at ? $shipment->last_event_at->copy() : null;
        $from = $lastTs ? $this->progress($departure, $arrival, $lastTs) : 0.0;
        $seconds = max(1, $arrival->getTimestamp() - $departure->getTimestamp());
        $written = 0;
        $geo = null;

        for ($k = (int) floor($from / $step + 1e-9) + 1; $k <= 19 && $k * $step < $progress - 1e-6; $k++) {
            $m = $k * $step;
            $geo ??= $this->geometry($shipment);
            [$path, $cum, $total] = $geo;
            [$lat, $lng] = $this->pointAt($path, $cum, $total * $m);

            $at = $departure->copy()->addSeconds((int) round($seconds * $m));
            if ($lastTs && $at->lt($lastTs)) {
                $at = $lastTs->copy();   // pembulatan detik: jangan lebih awal dari event terakhir
            }
            if ($at->gt(now())) {
                break;
            }

            $this->tracking->addEvent($shipment, [
                'event_type' => TrackingEvent::TYPE_CHECKPOINT,
                'latitude' => $lat,
                'longitude' => $lng,
                'location_label' => sprintf('En route · %d%%', round($m * 100)),
                'description' => '[Simulation] Position update.',
                'occurred_at' => $at,
                'estimated_arrival_at' => $arrival,
            ], $actor);
            $lastTs = $at;
            $written++;
        }

        return $written;
    }

    /** Tulis posisi terbaru bila kargo sudah bergeser cukup jauh (>100 m) sejak posisi tersimpan. */
    private function checkpoint(Shipment $shipment, User $actor, float $progress, Carbon $arrival): bool
    {
        [$path, $cum, $total] = $this->geometry($shipment);
        [$lat, $lng] = $this->pointAt($path, $cum, $total * $progress);

        if ($shipment->current_latitude !== null && $shipment->current_longitude !== null
            && RouteGeometry::distance([(float) $shipment->current_latitude, (float) $shipment->current_longitude], [$lat, $lng]) < 100) {
            return false;
        }

        $this->tracking->addEvent($shipment, [
            'event_type' => TrackingEvent::TYPE_CHECKPOINT,
            'latitude' => $lat,
            'longitude' => $lng,
            'location_label' => sprintf('En route · %d%%', round($progress * 100)),
            'description' => '[Simulation] Position update.',
            'estimated_arrival_at' => $arrival,
        ], $actor);

        return true;
    }

    private function move(Shipment $shipment, User $actor, int $stepPercent): TrackingEvent
    {
        [$path, $cum, $total] = $this->geometry($shipment);

        $here = ($shipment->current_latitude !== null && $shipment->current_longitude !== null)
            ? [(float) $shipment->current_latitude, (float) $shipment->current_longitude] : $path[0];
        $nearest = 0;
        $best = INF;
        foreach ($path as $i => $p) {
            $d = RouteGeometry::distance($p, $here);
            if ($d < $best) {
                $best = $d;
                $nearest = $i;
            }
        }

        $target = $cum[$nearest] + $total * $stepPercent / 100;
        if ($target >= $total - 1) {
            $text = self::autoDeliver() ? 'Arrived at destination.' : 'Arrived at destination. Waiting for payment and confirmation.';

            return $this->status($shipment, $actor, ShipmentStatus::ARRIVED, $shipment->destination, $text);
        }

        [$lat, $lng] = $this->pointAt($path, $cum, $target);

        return $this->tracking->addEvent($shipment, [
            'event_type' => TrackingEvent::TYPE_CHECKPOINT,
            'latitude' => $lat,
            'longitude' => $lng,
            'location_label' => sprintf('En route · %d%%', round($target / $total * 100)),
            'description' => '[Simulation] Position update.',
        ], $actor);
    }

    private function status(
        Shipment $shipment,
        User $actor,
        ShipmentStatus $to,
        $location,
        string $text,
        ?Carbon $at = null,
        ?Carbon $eta = null,
        bool $system = false,
    ): TrackingEvent {
        $data = [
            'event_type' => TrackingEvent::TYPE_STATUS_UPDATE,
            'status' => $to->value,
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'location_label' => $location->name,
            'description' => '[Simulation] '.$text,
        ];
        if ($at) {
            $data['occurred_at'] = $at;
        }
        if ($eta) {
            $data['estimated_arrival_at'] = $eta;
        }

        return $this->tracking->addEvent($shipment, $data, $actor, $system);
    }

    // ---- geometri ---------------------------------------------------------------

    /** @return array{0: array, 1: array<int, float>, 2: float} rute, jarak kumulatif, total (meter) */
    private function geometry(Shipment $shipment): array
    {
        $path = $this->routes->path($shipment);
        $cum = [0.0];
        for ($i = 1; $i < count($path); $i++) {
            $cum[] = $cum[$i - 1] + RouteGeometry::distance($path[$i - 1], $path[$i]);
        }

        return [$path, $cum, max(1.0, end($cum))];
    }

    /** @return array{0: float, 1: float} */
    private function pointAt(array $path, array $cum, float $distance): array
    {
        for ($i = 1; $i < count($path); $i++) {
            if ($cum[$i] >= $distance) {
                $f = ($distance - $cum[$i - 1]) / max(1e-9, $cum[$i] - $cum[$i - 1]);

                return [
                    $path[$i - 1][0] + ($path[$i][0] - $path[$i - 1][0]) * $f,
                    $path[$i - 1][1] + ($path[$i][1] - $path[$i - 1][1]) * $f,
                ];
            }
        }

        return end($path);
    }
}
