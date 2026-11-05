<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        return view('admin.warehouses', [
            'warehouses' => Warehouse::with('location')->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:12', 'unique:warehouses,code'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'capacity_kg' => ['required', 'integer', 'min:1', 'max:100000000'],
        ]);
        Warehouse::create($data);

        return back()->with('success', 'Warehouse added.');
    }

    public function toggle(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update(['is_active' => ! $warehouse->is_active]);

        return back()->with('success', 'Warehouse updated.');
    }
}
