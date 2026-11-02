<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShipmentFilterRequest;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ShipmentController extends Controller
{
    public function __construct(private ShipmentService $shipments)
    {
    }

    public function index(ShipmentFilterRequest $request)
    {
        $filters = $request->validated();

        $paginator = Shipment::query()
            ->with(['customer', 'carrier', 'origin', 'destination'])
            ->visibleTo($request->user())
            ->filter($filters)
            ->sortBy($filters['sort'] ?? null, $filters['dir'] ?? null)
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        return ShipmentResource::collection($paginator);
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $shipment = $this->shipments->create($request->validated(), $request->user());

        return (new ShipmentResource($shipment->load(['customer', 'carrier', 'origin', 'destination', 'items'])))
            ->response()->setStatusCode(201);
    }

    public function show(Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);

        return new ShipmentResource($shipment->load(['customer', 'carrier', 'origin', 'destination', 'items']));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): ShipmentResource
    {
        $shipment = $this->shipments->update($shipment, $request->validated(), $request->user());

        return new ShipmentResource($shipment->load(['customer', 'carrier', 'origin', 'destination', 'items']));
    }

    public function destroy(Shipment $shipment): JsonResponse
    {
        Gate::authorize('delete', $shipment);
        $this->shipments->delete($shipment, request()->user());

        return response()->json(['message' => 'Shipment deleted.']);
    }
}
