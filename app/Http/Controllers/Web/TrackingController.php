<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackingEventRequest;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;

class TrackingController extends Controller
{
    public function __construct(private TrackingService $tracking)
    {
    }

    public function store(StoreTrackingEventRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->tracking->addEvent($shipment, $request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Tracking event added.');
    }
}
