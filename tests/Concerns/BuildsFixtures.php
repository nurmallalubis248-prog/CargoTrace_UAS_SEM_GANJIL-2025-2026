<?php

namespace Tests\Concerns;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Support\Carbon;

trait BuildsFixtures
{
    protected function makeUser(string $role, ?string $email = null): User
    {
        $user = new User([
            'name' => ucfirst($role).' '.uniqid(),
            'email' => $email ?? $role.uniqid().'@example.test',
            'password' => 'password',
        ]);
        $user->role = $role;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    protected function makeCarrier(): Carrier
    {
        return Carrier::create(['name' => 'Carrier '.uniqid(), 'code' => strtoupper(substr(md5(uniqid()), 0, 6))]);
    }

    protected function makeLocation(string $name = 'Port', float $lat = 1.0, float $lng = 103.0): Location
    {
        return Location::create([
            'name' => $name.' '.uniqid(), 'code' => strtoupper(substr(md5(uniqid()), 0, 6)),
            'country' => 'SG', 'type' => 'PORT', 'latitude' => $lat, 'longitude' => $lng,
        ]);
    }

    /** Payload that passes StoreShipmentRequest (fresh carrier + locations every call). */
    protected function shipmentPayload(User $customer, array $override = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'carrier_id' => $this->makeCarrier()->id,
            'origin_id' => $this->makeLocation('Origin', 1.26, 103.84)->id,
            'destination_id' => $this->makeLocation('Destination', 51.95, 4.14)->id,
            'cargo_type' => 'GENERAL',
            'weight_kg' => 1000,
            'expected_departure_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'expected_arrival_at' => now()->addDays(20)->format('Y-m-d H:i:s'),
        ], $override);
    }

    protected function makeShipment(User $customer, User $creator, array $override = [], ?Carbon $at = null): Shipment
    {
        return app(ShipmentService::class)->create($this->shipmentPayload($customer, $override), $creator, $at);
    }
}
