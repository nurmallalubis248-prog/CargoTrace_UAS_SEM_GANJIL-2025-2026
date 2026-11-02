<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExceptionRequest;
use App\Http\Requests\UpdateExceptionRequest;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\RedirectResponse;

class ExceptionController extends Controller
{
    public function __construct(private ExceptionService $exceptions)
    {
    }

    public function store(StoreExceptionRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->exceptions->open($shipment, $request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Exception opened.');
    }

    public function update(UpdateExceptionRequest $request, ShipmentException $exception): RedirectResponse
    {
        $this->exceptions->update($exception, $request->validated(), $request->user());

        return redirect()->route('shipments.show', $exception->shipment_id)->with('success', 'Exception updated.');
    }
}
