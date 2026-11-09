<?php

namespace Database\Seeders;

use App\Enums\ExceptionStatus;
use App\Http\Controllers\Web\PaymentController;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ExceptionService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * DEMO DATA ONLY.
 * Port names/coordinates and carrier names are real-world references (approximate coordinates),
 * but every shipment, vessel position, delay and exception below is SYNTHETIC.
 * Replace the arrays with your own researched data before the final demo and document the source in the README.
 * Everything is created through the real service classes, so business rules are respected.
 */
class DatabaseSeeder extends Seeder
{
    /** code => [name, country, latitude, longitude] (approximate port coordinates) */
    private const LOCATIONS = [
        'SGSIN' => ['Port of Singapore', 'SG', 1.2644, 103.8400],
        'NLRTM' => ['Port of Rotterdam', 'NL', 51.9540, 4.1420],
        'CNSHA' => ['Port of Shanghai', 'CN', 31.3500, 121.6000],
        'USLAX' => ['Port of Los Angeles', 'US', 33.7292, -118.2620],
        'IDJKT' => ['Tanjung Priok (Jakarta)', 'ID', -6.1000, 106.8800],
        'AUSYD' => ['Port Botany (Sydney)', 'AU', -33.9700, 151.2200],
        'IDBLW' => ['Belawan (Medan)', 'ID', 3.7830, 98.7000],
        'INNSA' => ['Nhava Sheva (Mumbai)', 'IN', 18.9500, 72.9500],
        'KRPUS' => ['Port of Busan', 'KR', 35.1000, 129.0400],
        'DEHAM' => ['Port of Hamburg', 'DE', 53.5400, 9.9700],
        'MYPKG' => ['Port Klang', 'MY', 3.0000, 101.3900],
        'AEJEA' => ['Jebel Ali (Dubai)', 'AE', 25.0100, 55.0600],
        'LKCMB' => ['Port of Colombo', 'LK', 6.9500, 79.8500],
        'JPYOK' => ['Port of Yokohama', 'JP', 35.4500, 139.6500],
        'USNYC' => ['Port of New York & New Jersey', 'US', 40.6900, -74.1500],
        'IDCGK' => ['Soekarno-Hatta Airport (Jakarta)', 'ID', -6.1256, 106.6559],
        'SGSIA' => ['Changi Airport (Singapore)', 'SG', 1.3644, 103.9915],
        'AEDXB' => ['Dubai International Airport', 'AE', 25.2532, 55.3657],
        'GBLHR' => ['London Heathrow Airport', 'GB', 51.4700, -0.4543],
        'IDBDO' => ['Bandung (Warehouse)', 'ID', -6.9175, 107.6191],
        'IDSUB' => ['Surabaya (Warehouse)', 'ID', -7.2575, 112.7521],
        'IDMES' => ['Medan (Warehouse)', 'ID', 3.5952, 98.6722],
        'IDPLM' => ['Palembang (Warehouse)', 'ID', -2.9761, 104.7754],
    ];

    private const CARRIERS = [
        'MAEU' => 'Maersk', 'MSCU' => 'MSC', 'CMDU' => 'CMA CGM',
        'EGLV' => 'Evergreen', 'ONEY' => 'Ocean Network Express', 'HLCU' => 'Hapag-Lloyd',
        'GARC' => 'Garuda Cargo', 'SQCG' => 'Singapore Airlines Cargo', 'EKSC' => 'Emirates SkyCargo',
        'JNEX' => 'JNE Trucking', 'DHLR' => 'DHL Road Freight',
    ];
    private const AIR = ['GARC', 'SQCG', 'EKSC'];
    private const ROAD = ['JNEX', 'DHLR'];

    /** route key => [origin, destination, intermediate position reports [label, lat, lng]] */
    private const ROUTES = [
        'SIN-RTM' => ['SGSIN', 'NLRTM', [
            ['Off Colombo, Sri Lanka', 6.95, 79.85], ['Gulf of Aden', 12.5, 45.0],
            ['Port Said approach', 31.26, 32.31], ['Strait of Gibraltar', 36.0, -5.6],
        ]],
        'SHA-LAX' => ['CNSHA', 'USLAX', [['North Pacific (west)', 38.0, 160.0], ['North Pacific (east)', 36.0, -150.0]]],
        'JKT-SYD' => ['IDJKT', 'AUSYD', [['Lombok Strait', -8.7, 115.7], ['Timor Sea', -11.5, 126.0], ['Coral Sea', -17.0, 152.0]]],
        'BLW-NSA' => ['IDBLW', 'INNSA', [['Andaman Sea', 7.5, 94.0], ['Arabian Sea', 14.0, 75.0]]],
        'CGK-LHR' => ['IDCGK', 'GBLHR', [['Over Strait of Malacca', 5.5, 99.0], ['Over the Bay of Bengal', 15.0, 85.0], ['Over Arabian Sea', 20.0, 65.0], ['Over Turkey', 39.0, 33.0]]],
        'SIA-DXB' => ['SGSIA', 'AEDXB', [['Over Andaman Sea', 8.0, 95.0], ['Over Arabian Sea', 17.0, 68.0]]],
        'JKT-SUB' => ['IDJKT', 'IDSUB', [['Cikampek, West Java', -6.40, 107.45], ['Cirebon, West Java', -6.73, 108.55], ['Semarang, Central Java', -6.97, 110.42]]],
        'BDO-MES' => ['IDBDO', 'IDMES', [['Merak (Banten)', -5.93, 106.00], ['Bandar Lampung', -5.43, 105.26], ['Palembang, South Sumatra', -2.98, 104.77], ['Jambi', -1.61, 103.61]]],
    ];

    /** progress: created | booked | in_transit | delivered. dep_h = departure in hours from now (negative = past). */
    private const SHIPMENTS = [
        ['route' => 'SIN-RTM', 'carrier' => 'MAEU', 'cust' => 0, 'cargo' => 'CONTAINER_FCL', 'kg' => 18500, 'dep_h' => -336, 'days' => 30, 'progress' => 'in_transit', 'wp' => 2, 'delay_h' => 0],
        ['route' => 'SIN-RTM', 'carrier' => 'MSCU', 'cust' => 1, 'cargo' => 'REFRIGERATED', 'kg' => 12200, 'dep_h' => -480, 'days' => 28, 'progress' => 'in_transit', 'wp' => 3, 'delay_h' => 40,
            'exception' => ['PORT_CONGESTION', 'HIGH', 'Anchorage queue at Port Said; berth window moved.', 'OPEN']],
        ['route' => 'SHA-LAX', 'carrier' => 'CMDU', 'cust' => 0, 'cargo' => 'GENERAL', 'kg' => 24000, 'dep_h' => -240, 'days' => 16, 'progress' => 'in_transit', 'wp' => 2, 'delay_h' => 15,
            'exception' => ['WEATHER_DELAY', 'MEDIUM', 'Vessel reduced speed because of storm conditions.', 'INVESTIGATING']],
        ['route' => 'SHA-LAX', 'carrier' => 'EGLV', 'cust' => 1, 'cargo' => 'CONTAINER_FCL', 'kg' => 9800, 'dep_h' => -192, 'days' => 16, 'progress' => 'in_transit', 'wp' => 1, 'delay_h' => 0],
        ['route' => 'JKT-SYD', 'carrier' => 'ONEY', 'cust' => 0, 'cargo' => 'GENERAL', 'kg' => 6400, 'dep_h' => -216, 'days' => 11, 'progress' => 'in_transit', 'wp' => 2, 'delay_h' => 4],
        ['route' => 'BLW-NSA', 'carrier' => 'HLCU', 'cust' => 1, 'cargo' => 'BULK', 'kg' => 31000, 'dep_h' => -720, 'days' => 12, 'progress' => 'delivered', 'wp' => 2, 'delay_h' => 0,
            'exception' => ['DAMAGED', 'LOW', 'Minor packaging damage noted at discharge.', 'RESOLVED']],
        ['route' => 'SIN-RTM', 'carrier' => 'MAEU', 'cust' => 0, 'cargo' => 'GENERAL', 'kg' => 8800, 'dep_h' => -1440, 'days' => 30, 'progress' => 'delivered', 'wp' => 4, 'delay_h' => 5,
            'exception' => ['DOCUMENTATION_ISSUE', 'MEDIUM', 'Missing certificate of origin; corrected copy supplied.', 'RESOLVED']],
        ['route' => 'JKT-SYD', 'carrier' => 'ONEY', 'cust' => 1, 'cargo' => 'CONTAINER_LCL', 'kg' => 3100, 'dep_h' => 96, 'days' => 11, 'progress' => 'booked', 'wp' => 0, 'delay_h' => 0],
        ['route' => 'SHA-LAX', 'carrier' => 'MSCU', 'cust' => 0, 'cargo' => 'LIQUID', 'kg' => 21000, 'dep_h' => 216, 'days' => 16, 'progress' => 'created', 'wp' => 0, 'delay_h' => 0],
        ['route' => 'CGK-LHR', 'carrier' => 'GARC', 'cust' => 0, 'cargo' => 'GENERAL', 'kg' => 1800, 'dep_h' => -14, 'days' => 1, 'progress' => 'in_transit', 'wp' => 3, 'delay_h' => 0],
        ['route' => 'SIA-DXB', 'carrier' => 'EKSC', 'cust' => 1, 'cargo' => 'REFRIGERATED', 'kg' => 950, 'dep_h' => -12, 'days' => 1, 'progress' => 'in_transit', 'wp' => 2, 'delay_h' => 8,
            'exception' => ['WEATHER_DELAY', 'LOW', 'Holding pattern due to thunderstorms near the destination.', 'OPEN']],
        ['route' => 'JKT-SUB', 'carrier' => 'JNEX', 'cust' => 0, 'cargo' => 'GENERAL', 'kg' => 4200, 'dep_h' => -30, 'days' => 2, 'progress' => 'in_transit', 'wp' => 2, 'delay_h' => 0],
        ['route' => 'BDO-MES', 'carrier' => 'DHLR', 'cust' => 1, 'cargo' => 'CONTAINER_LCL', 'kg' => 7600, 'dep_h' => -60, 'days' => 4, 'progress' => 'in_transit', 'wp' => 3, 'delay_h' => 30,
            'exception' => ['MISROUTED', 'HIGH', 'Truck diverted because of a road closure; new ETA issued.', 'INVESTIGATING']],
    ];

    /** @var array<int,int> warehouse ids created in run(), used when seeding shipments */
    private array $whIds = [];

    public function run(): void
    {
        $admin = $this->user('Admin CargoTrace', 'admin@cargotrace.test', User::ROLE_ADMIN);
        $operator = $this->user('Logistics Operator', 'operator@cargotrace.test', User::ROLE_OPERATOR);
        $customers = [
            $this->user('PT Nusantara Export', 'customer1@cargotrace.test', User::ROLE_CUSTOMER),
            $this->user('Samudra Trading Co.', 'customer2@cargotrace.test', User::ROLE_CUSTOMER),
        ];

        $locations = [];
        foreach (self::LOCATIONS as $code => [$name, $country, $lat, $lng]) {
            $locations[$code] = Location::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'country' => $country, 'type' => str_contains($name, 'Airport') ? 'AIRPORT' : (str_contains($name, 'Warehouse') ? 'WAREHOUSE' : 'PORT'), 'latitude' => $lat, 'longitude' => $lng]
            );
        }

        $carriers = [];
        foreach (self::CARRIERS as $code => $name) {
            $carriers[$code] = Carrier::updateOrCreate(['code' => $code], ['name' => $name, 'transport_mode' => in_array($code, self::AIR, true) ? 'AIR' : (in_array($code, self::ROAD, true) ? 'ROAD' : 'SEA')]);
        }

        $this->whIds = [];
        foreach ([['JKT-WH', 'Jakarta Distribution Hub', 'IDJKT', 60000], ['SUB-WH', 'Surabaya Warehouse', 'IDSUB', 40000],
                  ['MES-WH', 'Medan Warehouse', 'IDMES', 30000], ['BDO-WH', 'Bandung Warehouse', 'IDBDO', 25000]] as [$code, $name, $loc, $cap]) {
            $this->whIds[] = \App\Models\Warehouse::updateOrCreate(['code' => $code], ['name' => $name, 'location_id' => ($locations[$loc] ?? null)?->id, 'capacity_kg' => $cap])->id;
        }

        if (Shipment::withTrashed()->exists()) {
            return; // shipments already seeded
        }

        foreach (self::SHIPMENTS as $def) {
            $this->seedShipment($def, $locations, $carriers, $customers, $operator);
        }
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            return $user;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => 'password']);
        $user->role = $role; // role is not mass-assignable
        $user->email_verified_at = now(); // demo accounts are pre-verified
        $user->save();

        return $user;
    }

    private function seedShipment(array $d, array $locations, array $carriers, array $customers, User $op): void
    {
        $shipments = app(ShipmentService::class);
        $tracking = app(TrackingService::class);
        $exceptions = app(ExceptionService::class);

        [$originCode, $destCode, $waypoints] = self::ROUTES[$d['route']];
        $origin = $locations[$originCode];
        $dest = $locations[$destCode];

        $now = now();
        $dep = $now->copy()->addHours($d['dep_h']);
        $exp = $dep->copy()->addDays($d['days']);
        $fmt = 'Y-m-d H:i:s';

        $createdAt = match ($d['progress']) {
            'created' => $now->copy()->subHours(6),
            'booked' => $now->copy()->subHours(30),
            default => $dep->copy()->subDays(3),
        };

        $shipment = $shipments->create([
            'reference_number' => 'PO-'.random_int(10000, 99999),
            'customer_id' => $customers[$d['cust']]->id,
            'carrier_id' => $carriers[$d['carrier']]->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'warehouse_id' => $this->whIds[crc32($d['route'].$d['carrier']) % count($this->whIds)],
            'cargo_type' => $d['cargo'],
            'weight_kg' => $d['kg'],
            'expected_departure_at' => $dep->format($fmt),
            'expected_arrival_at' => $exp->format($fmt),
            'items' => [['description' => ucfirst(strtolower(str_replace('_', ' ', $d['cargo']))).' consignment', 'quantity' => 1, 'weight_kg' => $d['kg']]],
        ], $op, $createdAt);

        $event = fn (array $e) => $tracking->addEvent($shipment, $e, $op);
        $status = fn (string $s, Carbon $at, string $text, ?Location $loc = null) => $event([
            'event_type' => 'STATUS_UPDATE', 'status' => $s, 'occurred_at' => $at->format('Y-m-d H:i:s'),
            'location_id' => $loc?->id, 'description' => $text,
        ]);

        if ($d['progress'] === 'booked') {
            $status('BOOKED', $createdAt->copy()->addHours(3), 'Booking confirmed with carrier.', $origin);
        }

        $end = $now;

        if (in_array($d['progress'], ['in_transit', 'delivered'], true)) {
            $status('BOOKED', $dep->copy()->subDays(2), 'Booking confirmed with carrier.', $origin);
            $status('PICKED_UP', $dep, 'Cargo picked up and gated in at origin terminal.', $origin);
            $start = $dep->copy()->addHours(6);
            $status('IN_TRANSIT', $start, 'Vessel departed origin port.', $origin);

            $end = $d['progress'] === 'delivered' ? $exp->copy()->addHours($d['delay_h']) : $now;
            $span = $end->copy()->subHour()->getTimestamp() - $start->getTimestamp();
            $reached = array_slice($waypoints, 0, $d['wp']);
            $n = count($reached);

            $last = $start;
            foreach ($reached as $i => [$label, $lat, $lng]) {
                $last = $start->copy()->addSeconds((int) ($span * ($i + 1) / ($n + 1)));
                $event([
                    'event_type' => 'CHECKPOINT', 'occurred_at' => $last->format($fmt),
                    'location_label' => $label, 'latitude' => $lat, 'longitude' => $lng,
                    'description' => 'Position report (demo data).',
                ]);
            }

            if ($d['progress'] === 'in_transit' && $d['delay_h'] > 0) {
                $event([
                    'event_type' => 'ETA_UPDATE', 'occurred_at' => $last->copy()->addMinutes(30)->format($fmt),
                    'estimated_arrival_at' => $exp->copy()->addHours($d['delay_h'])->format($fmt),
                    'description' => 'Carrier revised the ETA.',
                ]);
            }

            if ($d['progress'] === 'delivered') {
                $status('ARRIVED', $end, 'Cargo arrived at the destination terminal.', $dest);
                $confirmAt = $end->copy()->addHours(3);
                if ($confirmAt->gt($now)) {
                    $confirmAt = $now->copy();
                }
                $this->payAndConfirm($shipment, $customers[$d['cust']], $end, $confirmAt);
            }
        }

        if (isset($d['exception'])) {
            [$type, $severity, $text, $finalStatus] = $d['exception'];
            $at = $end->copy()->subHours(2);
            $exception = $exceptions->open($shipment->refresh(), ['type' => $type, 'severity' => $severity, 'description' => $text], $op, $at);

            if ($finalStatus === ExceptionStatus::INVESTIGATING->value) {
                $exceptions->update($exception, ['status' => 'INVESTIGATING'], $op, $at->copy()->addHour());
            } elseif ($finalStatus === ExceptionStatus::RESOLVED->value) {
                $exceptions->update($exception, ['status' => 'RESOLVED', 'resolution_notes' => 'Handled with carrier and customer.'], $op, $at->copy()->addHours(8));
            }
        }
    }

    /** Demo data for a completed delivery: the customer paid (demo transaction) and confirmed receipt. */
    private function payAndConfirm(Shipment $shipment, User $customer, Carbon $arrivedAt, Carbon $confirmAt): void
    {
        $payment = $shipment->payments()->create([
            'user_id' => $customer->id,
            'order_id' => 'CT-'.$shipment->id.'-DEMO-'.random_int(100, 999),
            'amount' => PaymentController::amountFor($shipment),
            'status' => 'PAID',
            'payment_type' => ['qris', 'bca_va', 'bni_va', 'bri_va'][random_int(0, 3)],
        ]);
        $paidAt = $arrivedAt->copy()->addHour();
        if ($paidAt->gt($confirmAt)) {
            $paidAt = $confirmAt->copy();
        }
        $settled = random_int(0, 1) === 1;
        $payment->forceFill([
            'paid_at' => $paidAt,
            'payout_status' => $settled ? 'SETTLED' : 'PENDING_SETTLEMENT',
            'settled_at' => $settled ? $paidAt->copy()->addDays(2)->min(now()) : null,
        ])->save();

        app(TrackingService::class)->addEvent($shipment, [
            'event_type' => 'STATUS_UPDATE', 'status' => 'DELIVERED', 'occurred_at' => $confirmAt->format('Y-m-d H:i:s'),
            'location_id' => $shipment->destination_id, 'description' => 'Receipt confirmed by the customer (demo data).',
        ], $customer);
    }
}
