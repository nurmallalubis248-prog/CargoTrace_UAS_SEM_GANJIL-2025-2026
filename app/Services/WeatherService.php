<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Current weather at a coordinate (Open-Meteo, via Laravel HTTP Client).
 * Informational only: it is NOT used to predict or prove delays.
 * Never throws: timeouts, HTTP errors and empty payloads become {available: false, message}.
 */
class WeatherService
{
    private const CODES = [
        0 => 'Clear sky', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Fog', 48 => 'Rime fog',
        51 => 'Light drizzle', 53 => 'Drizzle', 55 => 'Dense drizzle',
        61 => 'Light rain', 63 => 'Rain', 65 => 'Heavy rain',
        71 => 'Light snow', 73 => 'Snow', 75 => 'Heavy snow',
        80 => 'Rain showers', 81 => 'Rain showers', 82 => 'Violent rain showers',
        95 => 'Thunderstorm', 96 => 'Thunderstorm with hail', 99 => 'Thunderstorm with heavy hail',
    ];

    public function current(float $latitude, float $longitude): array
    {
        $key = sprintf('weather:%.2f:%.2f', $latitude, $longitude);

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        try {
            $params = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current' => 'temperature_2m,wind_speed_10m,precipitation,weather_code',
                'timezone' => 'UTC',
            ];
            if ($apiKey = config('services.weather.api_key')) {
                $params['apikey'] = $apiKey; // from .env, never hard-coded
            }

            $response = Http::timeout(config('services.http_timeout', 5))
                ->acceptJson()
                ->get(config('services.weather.base_url'), $params);

            if ($response->failed()) {
                return $this->unavailable('Weather service responded with HTTP '.$response->status().'.');
            }

            $current = $response->json('current');
            if (empty($current) || ! is_array($current) || ! isset($current['temperature_2m'])) {
                return $this->unavailable('No weather data is available for this location.');
            }

            $result = [
                'available' => true,
                'source' => 'Open-Meteo',
                'observed_at' => $current['time'] ?? null,
                'temperature_c' => $current['temperature_2m'],
                'wind_kmh' => $current['wind_speed_10m'] ?? null,
                'precipitation_mm' => $current['precipitation'] ?? null,
                'condition' => self::CODES[$current['weather_code'] ?? -1] ?? 'Unknown',
                'message' => null,
            ];

            Cache::put($key, $result, now()->addMinutes(10));

            return $result;
        } catch (Throwable $e) {
            Log::warning('Weather lookup failed: '.$e->getMessage());

            return $this->unavailable('Weather service is currently unreachable (timeout or network error).');
        }
    }

    private function unavailable(string $message): array
    {
        return ['available' => false, 'source' => 'Open-Meteo', 'message' => $message];
    }
}
