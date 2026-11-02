<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Address / place name -> coordinates (Nominatim / OpenStreetMap).
 * Results are cached (Nominatim usage policy) and errors never bubble up as 500.
 */
class GeocodingService
{
    public function search(string $query): array
    {
        $query = trim($query);
        $key = 'geocode:'.md5(mb_strtolower($query));

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        try {
            $response = Http::timeout(config('services.http_timeout', 5))
                ->withHeaders(['User-Agent' => config('services.geocoding.user_agent')])
                ->acceptJson()
                ->get(config('services.geocoding.base_url'), [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 5,
                ]);

            if ($response->failed()) {
                return ['available' => false, 'results' => [], 'message' => 'Geocoding service responded with HTTP '.$response->status().'.'];
            }

            $rows = $response->json();
            if (! is_array($rows) || $rows === []) {
                return ['available' => true, 'results' => [], 'message' => 'No places found for this query.'];
            }

            $results = collect($rows)
                ->filter(fn ($r) => isset($r['lat'], $r['lon']))
                ->map(fn ($r) => [
                    'name' => $r['display_name'] ?? $query,
                    'latitude' => (float) $r['lat'],
                    'longitude' => (float) $r['lon'],
                ])
                ->values()
                ->all();

            $payload = ['available' => true, 'results' => $results, 'message' => null];
            Cache::put($key, $payload, now()->addDay());

            return $payload;
        } catch (Throwable $e) {
            Log::warning('Geocoding lookup failed: '.$e->getMessage());

            return ['available' => false, 'results' => [], 'message' => 'Geocoding service is currently unreachable (timeout or network error).'];
        }
    }
}
