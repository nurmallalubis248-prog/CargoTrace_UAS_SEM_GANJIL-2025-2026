<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShipmentFilterRequest;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ShipmentService;
use App\Services\SimulationService;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['customer', 'carrier', 'origin', 'destination', 'exceptions', 'items'])
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

        // Customer: the Customer dropdown is hidden in the form and customer_id is filled with their own id on store.
        return view('shipments.create', $this->formData() + [
            'shipment' => new Shipment(),
            'isCustomerForm' => (bool) request()->user()?->isCustomer(),
        ]);
    }

    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        $shipment = $this->shipments->create($request->validated(), $request->user());

        $message = $request->user()->isCustomer()
            ? "Shipment request {$shipment->shipment_number} submitted and is waiting for confirmation."
            : "Shipment {$shipment->shipment_number} created.";

        return redirect()->route('shipments.show', $shipment)->with('success', $message);
    }

    public function show(Shipment $shipment): View
    {
        Gate::authorize('view', $shipment);

        // Pastikan keadaan sesuai jam sekarang sebelum ditampilkan (aman diulang; refresh tidak mengubah apa pun).
        app(SimulationService::class)->catchUp($shipment);
        $shipment->refresh();

        $shipment->load(['customer', 'creator', 'carrier', 'origin', 'destination', 'items']);

        $events = $shipment->trackingEvents()->with(['creator', 'location'])
            ->orderBy('occurred_at')->orderBy('id')->get();
        $exceptions = $shipment->exceptions()->with('reporter')->orderByDesc('detected_at')->get();

        $mapData = [
            'mode' => $shipment->carrier->transport_mode ?? 'SEA',
            'number' => $shipment->shipment_number,
            'delayed' => (bool) $shipment->is_delayed,
            'done' => $shipment->current_status->value === 'DELIVERED',
            'arrived' => $shipment->current_status->value === 'ARRIVED',
            'last_ts' => $shipment->last_event_at ? $shipment->last_event_at->getTimestamp() * 1000 : null,
            'dep_ts' => SimulationService::windowMs($shipment)[0] ?? null,   // jadwal untuk posisi kendaraan yang konsisten
            'arr_ts' => SimulationService::windowMs($shipment)[1] ?? null,
            'eta_ts' => ($shipment->estimated_arrival_at ?? $shipment->expected_arrival_at)?->getTimestamp() * 1000 ?: null,
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

    /** Tiny status probe the shipment page polls so customers see new tracking events without reloading. */
    public function live(Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        app(SimulationService::class)->catchUp($shipment);
        $shipment->refresh();

        return response()->json([
            'status' => $shipment->current_status->value,
            'events' => $shipment->trackingEvents()->count(),
            'last_event_at' => $shipment->last_event_at?->getTimestamp(),
            'delayed' => (bool) $shipment->is_delayed,
            'done' => $shipment->current_status->isTerminal(),
        ]);
    }

    /**
     * "Confirm received": only the shipment's customer, only after ARRIVED.
     * The rules live in TrackingService (a violation redirects back with an error message).
     */
    public function confirm(Request $request, Shipment $shipment, TrackingService $tracking): RedirectResponse
    {
        abort_unless((int) $request->user()->id === (int) $shipment->customer_id, 403);

        $tracking->addEvent($shipment, [
            'event_type' => TrackingEvent::TYPE_STATUS_UPDATE,
            'status' => 'DELIVERED',
            'location_id' => $shipment->destination_id,
            'description' => 'Receipt confirmed by the customer.',
        ], $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Thank you! Delivery confirmed.');
    }

    public function edit(Shipment $shipment): View
    {
        Gate::authorize('update', $shipment);

        return view('shipments.edit', $this->formData() + ['shipment' => $shipment->load('items'), 'isCustomerForm' => false]);
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->shipments->update($shipment, $request->validated(), $request->user());

        // "Save and approve": complete the request data and process it in one step.
        if ($request->boolean('approve') && Gate::forUser($request->user())->allows('approve', $shipment->refresh())) {
            $this->shipments->approve($shipment, $request->user());

            return redirect()->route('shipments.show', $shipment)
                ->with('success', "Shipment {$shipment->shipment_number} approved and is now being processed.");
        }

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment updated.');
    }

    /** Admin / operator process a customer request: PENDING -> BOOKED, notifying the customer and the other staff role. */
    public function approve(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('approve', $shipment);
        $this->shipments->approve($shipment, $request->user());

        return redirect()->route('shipments.show', $shipment)
            ->with('success', "Shipment {$shipment->shipment_number} approved and is now being processed.");
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
