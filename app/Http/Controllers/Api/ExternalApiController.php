<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\GeocodingService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExternalApiController extends Controller
{
    /**
     * Weather at the shipment's current location (falls back to the origin port).
     * Always answers 200 with {available: bool}; a failing provider never breaks the page.
     */
    public function weather(Shipment $shipment, WeatherService $weather): JsonResponse
    {
        Gate::authorize('view', $shipment);

        $shipment->loadMissing('origin');

        if ($shipment->current_latitude !== null && $shipment->current_longitude !== null) {
            $lat = $shipment->current_latitude;
            $lng = $shipment->current_longitude;
            $label = $shipment->current_location_label ?? 'Current position';
        } else {
            $lat = $shipment->origin->latitude;
            $lng = $shipment->origin->longitude;
            $label = $shipment->origin->name;
        }

        return response()->json($weather->current((float) $lat, (float) $lng) + [
            'location' => ['label' => $label, 'latitude' => $lat, 'longitude' => $lng],
            'disclaimer' => 'Informational only. Weather is not used to predict or prove shipment delays.',
        ]);
    }

    /** Staff only: place name -> coordinates (used when adding locations). */
    public function geocode(Request $request, GeocodingService $geocoding): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:3', 'max:150']]);

        return response()->json($geocoding->search($data['q']));
    }
}
