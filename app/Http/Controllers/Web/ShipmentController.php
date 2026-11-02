<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShipmentFilterRequest;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(private ShipmentService $shipments, private EtaService $eta)
    {
    }

    public function index(ShipmentFilterRequest $request): View
    {
        $filters = $request->validated();

        $shipments = Shipment::query()
            ->with(['customer', 'carrier', 'origin', 'destination'])
            ->visibleTo($request->user())
            ->filter($filters)
            ->sortBy($filters['sort'] ?? null, $filters['dir'] ?? null)
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString();

        return view('shipments.index', [
            'shipments' => $shipments,
            'filters' => $filters,
            'carriers' => Carrier::orderBy('name')->get(['id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Shipment::class);

        return view('shipments.create', $this->formData() + ['shipment' => new Shipment()]);
    }

    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        $shipment = $this->shipments->create($request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)
            ->with('success', "Shipment {$shipment->shipment_number} created.");
    }

    public function show(Shipment $shipment): View
    {
        Gate::authorize('view', $shipment);

        $shipment->load(['customer', 'creator', 'carrier', 'origin', 'destination', 'items']);

        $events = $shipment->trackingEvents()->with(['creator', 'location'])
            ->orderBy('occurred_at')->orderBy('id')->get();
        $exceptions = $shipment->exceptions()->with('reporter')->orderByDesc('detected_at')->get();

        $mapData = [
            'origin' => ['name' => $shipment->origin->name, 'lat' => $shipment->origin->latitude, 'lng' => $shipment->origin->longitude],
            'destination' => ['name' => $shipment->destination->name, 'lat' => $shipment->destination->latitude, 'lng' => $shipment->destination->longitude],
            'points' => $events->filter(fn ($e) => $e->latitude !== null && $e->longitude !== null)->map(fn ($e) => [
                'lat' => $e->latitude,
                'lng' => $e->longitude,
                'type' => $e->event_type,
                'status' => $e->status?->value,
                'exception' => $e->is_exception,
                'time' => $e->occurred_at->format('Y-m-d H:i').' UTC',
                'label' => $e->location_label,
            ])->values()->all(),
        ];

        return view('shipments.show', [
            'shipment' => $shipment,
            'events' => $events,
            'exceptions' => $exceptions,
            'delay' => $this->eta->evaluate($shipment),
            'mapData' => $mapData,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Shipment $shipment): View
    {
        Gate::authorize('update', $shipment);

        return view('shipments.edit', $this->formData() + ['shipment' => $shipment->load('items')]);
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->shipments->update($shipment, $request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment updated.');
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        Gate::authorize('delete', $shipment);
        $this->shipments->delete($shipment, request()->user());

        return redirect()->route('shipments.index')->with('success', 'Shipment deleted.');
    }

    private function formData(): array
    {
        return [
            'customers' => User::where('role', User::ROLE_CUSTOMER)->orderBy('name')->get(['id', 'name', 'email']),
            'carriers' => Carrier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'cargoTypes' => config('cargotrace.cargo_types'),
        ];
    }
}
