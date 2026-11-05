<?php

return [
    // Shared timeout (seconds) for every outgoing HTTP request.
    'http_timeout' => (int) env('EXTERNAL_API_TIMEOUT', 5),

    // Open-Meteo (no API key required for non-commercial use).
    // If you switch to a keyed provider, put the key in .env -> WEATHER_API_KEY.
    'weather' => [
        'base_url' => env('WEATHER_BASE_URL', 'https://api.open-meteo.com/v1/forecast'),
        'api_key' => env('WEATHER_API_KEY'),
    ],

    // Nominatim (OpenStreetMap geocoding). Policy: identify your app via User-Agent, max 1 req/s, cache results.
    'geocoding' => [
        'base_url' => env('GEOCODING_BASE_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env('GEOCODING_USER_AGENT', 'CargoTrace-UAS/1.0'),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    ],

    'erp' => [
        'api_key' => env('ERP_API_KEY'),
        'webhook_url' => env('ERP_WEBHOOK_URL'),
    ],

];
