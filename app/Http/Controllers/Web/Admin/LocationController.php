<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Models\Location;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('admin.locations', ['locations' => Location::orderBy('name')->get()]);
    }

    public function store(StoreLocationRequest $request, AuditService $audit): RedirectResponse
    {
        $location = Location::create($request->validated());
        $audit->log($request->user(), 'location.created', $location, ['code' => $location->code]);

        return back()->with('success', 'Location added.');
    }

    public function toggle(Request $request, Location $location, AuditService $audit): RedirectResponse
    {
        $location->is_active = ! $location->is_active;
        $location->save();
        $audit->log($request->user(), 'location.toggled', $location, ['is_active' => $location->is_active]);

        return back()->with('success', 'Location updated.');
    }
}
