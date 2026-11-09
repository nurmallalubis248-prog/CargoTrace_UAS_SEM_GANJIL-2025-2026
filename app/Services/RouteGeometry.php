<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** The route a shipment travels: real roads (OSRM) for ROAD carriers, otherwise a great-circle line. */
class RouteGeometry
{
    private const EARTH_RADIUS_M = 6371000.0;

    /** @return array<int, array{0: float, 1: float}> [lat, lng] points from origin to destination */
    public function path(Shipment $shipment): array
    {
        $a = [(float) $shipment->origin->latitude, (float) $shipment->origin->longitude];
        $b = [(float) $shipment->destination->latitude, (float) $shipment->destination->longitude];

        if (($shipment->carrier->transport_mode ?? 'SEA') === 'ROAD') {
            $road = $this->road($a, $b);
            if ($road !== null) {
                return $road;
            }
        }

        return $this->greatCircle($a, $b);
    }

    /** Haversine distance in metres. */
    public static function distance(array $a, array $b): float
    {
        $r = M_PI / 180;
        $dLat = ($b[0] - $a[0]) * $r;
        $dLng = ($b[1] - $a[1]) * $r;
        $h = sin($dLat / 2) ** 2 + cos($a[0] * $r) * cos($b[0] * $r) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_M * asin(min(1.0, sqrt($h)));
    }

    /** @return array<int, array{0: float, 1: float}>|null */
    private function road(array $a, array $b): ?array
    {
        $key = sprintf('route:%.3f,%.3f:%.3f,%.3f', $a[0], $a[1], $b[0], $b[1]);

        return Cache::remember($key, now()->addDays(7), function () use ($a, $b) {
            try {
                $response = Http::timeout(8)->get(
                    sprintf('https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F', $a[1], $a[0], $b[1], $b[0]),
                    ['overview' => 'full', 'geometries' => 'geojson']
                );
                $coords = $response->json('routes.0.geometry.coordinates');
                if (! $response->successful() || ! is_array($coords) || count($coords) < 2) {
                    return null;
                }

                return array_map(fn ($c) => [(float) $c[1], (float) $c[0]], $coords);
            } catch (\Throwable) {
                return null;   // offline or blocked: caller falls back to the great-circle line
            }
        });
    }

    /** @return array<int, array{0: float, 1: float}> */
    private function greatCircle(array $a, array $b): array
    {
        $r = M_PI / 180;
        $p1 = $a[0] * $r; $l1 = $a[1] * $r; $p2 = $b[0] * $r; $l2 = $b[1] * $r;
        $d = 2 * asin(min(1.0, sqrt(sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin(($l2 - $l1) / 2) ** 2)));
        if ($d < 1e-9) {
            return [$a, $b];
        }

        $n = max(8, min(200, (int) round($d * self::EARTH_RADIUS_M / 50000)));
        $points = [];
        for ($i = 0; $i <= $n; $i++) {
            $f = $i / $n;
            $A = sin((1 - $f) * $d) / sin($d);
            $B = sin($f * $d) / sin($d);
            $x = $A * cos($p1) * cos($l1) + $B * cos($p2) * cos($l2);
            $y = $A * cos($p1) * sin($l1) + $B * cos($p2) * sin($l2);
            $z = $A * sin($p1) + $B * sin($p2);
            $points[] = [atan2($z, hypot($x, $y)) / $r, atan2($y, $x) / $r];
        }

        return $points;
    }
}
