<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarrierRequest;
use App\Models\Carrier;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarrierController extends Controller
{
    public function index(): View
    {
        return view('admin.carriers', ['carriers' => Carrier::withCount('shipments')->orderBy('name')->get()]);
    }

    public function store(StoreCarrierRequest $request, AuditService $audit): RedirectResponse
    {
        $carrier = Carrier::create($request->validated());
        $audit->log($request->user(), 'carrier.created', $carrier, ['code' => $carrier->code]);

        return back()->with('success', 'Carrier added.');
    }

    public function toggle(Request $request, Carrier $carrier, AuditService $audit): RedirectResponse
    {
        $carrier->is_active = ! $carrier->is_active;
        $carrier->save();
        $audit->log($request->user(), 'carrier.toggled', $carrier, ['is_active' => $carrier->is_active]);

        return back()->with('success', 'Carrier updated.');
    }
}
