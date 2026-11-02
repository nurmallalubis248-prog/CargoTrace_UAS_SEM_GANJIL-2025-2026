<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackingEventRequest;
use App\Http\Resources\ShipmentResource;
use App\Http\Resources\TrackingEventResource;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TrackingController extends Controller
{
    public function __construct(private TrackingService $tracking)
    {
    }

    /** Chronological timeline (oldest first). */
    public function index(Shipment $shipment)
    {
        Gate::authorize('view', $shipment);

        $events = $shipment->trackingEvents()->with('creator')
            ->orderBy('occurred_at')->orderBy('id')->get();

        return TrackingEventResource::collection($events);
    }

    public function store(StoreTrackingEventRequest $request, Shipment $shipment): JsonResponse
    {
        $event = $this->tracking->addEvent($shipment, $request->validated(), $request->user());

        return response()->json([
            'data' => new TrackingEventResource($event->load('creator')),
            'shipment' => new ShipmentResource($shipment->refresh()->load(['carrier', 'origin', 'destination'])),
        ], 201);
    }
}
