<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'code' => strtoupper(trim((string) $request->query('code', ''))),
            'location_id' => ctype_digit((string) $request->query('location_id', '')) ? (int) $request->query('location_id') : null,
            'location_code' => strtoupper(trim((string) $request->query('location_code', ''))),
            'cap_min' => $num($request->query('cap_min')), 'cap_max' => $num($request->query('cap_max')),
            'load_min' => $num($request->query('load_min')), 'load_max' => $num($request->query('load_max')),
            'per_page' => in_array((int) $request->query('per_page'), [10, 25, 50], true) ? (int) $request->query('per_page') : 10,
        ];

        $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';

        // Muatan = berat pengiriman yang masih aktif di gudang (sama dengan Warehouse::loadKg()), dihitung di SQL agar bisa difilter.
        $loadSub = fn () => Shipment::query()
            ->selectRaw('COALESCE(SUM(shipments.weight_kg), 0)')
            ->whereColumn('shipments.warehouse_id', 'warehouses.id')
            ->whereNotIn('shipments.current_status', ['DELIVERED', 'CANCELLED']);

        $warehouses = Warehouse::query()
            ->with('location')
            ->withCount('shipments')
            ->addSelect(['warehouses.*', 'load_kg' => $loadSub()])
            ->when($filters['name'] !== '', fn ($q) => $q->where('warehouses.name', 'like', $like($filters['name'])))
            ->when($filters['code'] !== '', fn ($q) => $q->where('warehouses.code', 'like', $like($filters['code'])))
            ->when($filters['location_id'] !== null, fn ($q) => $q->where('warehouses.location_id', $filters['location_id']))
            ->when($filters['location_code'] !== '', fn ($q) => $q->whereHas('location', fn ($l) => $l->where('code', 'like', $like($filters['location_code']))))
            ->when($filters['cap_min'] !== null, fn ($q) => $q->where('warehouses.capacity_kg', '>=', $filters['cap_min']))
            ->when($filters['cap_max'] !== null, fn ($q) => $q->where('warehouses.capacity_kg', '<=', $filters['cap_max']))
            ->when($filters['load_min'] !== null, fn ($q) => $q->where($loadSub(), '>=', $filters['load_min']))
            ->when($filters['load_max'] !== null, fn ($q) => $q->where($loadSub(), '<=', $filters['load_max']))
            ->orderBy('warehouses.name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.warehouses', [
            'warehouses' => $warehouses,
            'filters' => $filters,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:12', 'unique:warehouses,code'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'capacity_kg' => ['required', 'integer', 'min:1', 'max:100000000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $warehouse = Warehouse::create($data);
        $audit->log($request->user(), 'warehouse.created', $warehouse, ['code' => $warehouse->code]);

        return back()->with('success', 'Warehouse added.');
    }

    public function update(Request $request, Warehouse $warehouse, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:12', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'capacity_kg' => ['required', 'integer', 'min:1', 'max:100000000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $warehouse->fill($data)->save();
        $audit->log($request->user(), 'warehouse.updated', $warehouse, $warehouse->getChanges());

        return back()->with('success', 'Warehouse updated.');
    }

    public function toggle(Request $request, Warehouse $warehouse, AuditService $audit): RedirectResponse
    {
        $warehouse->update(['is_active' => ! $warehouse->is_active]);
        $audit->log($request->user(), 'warehouse.toggled', $warehouse, ['is_active' => $warehouse->is_active]);

        return back()->with('success', 'Warehouse updated.');
    }

    public function destroy(Request $request, Warehouse $warehouse, AuditService $audit): RedirectResponse
    {
        if (Shipment::withTrashed()->where('warehouse_id', $warehouse->id)->exists()) {
            return back()->with('error', 'This warehouse is used by shipments and cannot be deleted. Deactivate it instead.');
        }

        $audit->log($request->user(), 'warehouse.deleted', $warehouse, ['code' => $warehouse->code, 'name' => $warehouse->name]);
        $warehouse->delete();

        return back()->with('success', 'Warehouse deleted.');
    }
}
