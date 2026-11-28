<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Shipment;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'code' => strtoupper(trim((string) $request->query('code', ''))),
            'country' => strtoupper(trim((string) $request->query('country', ''))),
            'type' => in_array($request->query('type'), Location::TYPES, true) ? $request->query('type') : '',
            'lat_min' => $num($request->query('lat_min')), 'lat_max' => $num($request->query('lat_max')),
            'lng_min' => $num($request->query('lng_min')), 'lng_max' => $num($request->query('lng_max')),
            'per_page' => in_array((int) $request->query('per_page'), [10, 25, 50], true) ? (int) $request->query('per_page') : 10,
        ];

        $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';

        $locations = Location::query()
            ->withCount(['originShipments', 'destinationShipments'])
            ->when($filters['name'] !== '', fn ($q) => $q->where('name', 'like', $like($filters['name'])))
            ->when($filters['code'] !== '', fn ($q) => $q->where('code', 'like', $like($filters['code'])))
            ->when($filters['country'] !== '', fn ($q) => $q->where('country', $filters['country']))
            ->when($filters['type'] !== '', fn ($q) => $q->where('type', $filters['type']))
            ->when($filters['lat_min'] !== null, fn ($q) => $q->where('latitude', '>=', $filters['lat_min']))
            ->when($filters['lat_max'] !== null, fn ($q) => $q->where('latitude', '<=', $filters['lat_max']))
            ->when($filters['lng_min'] !== null, fn ($q) => $q->where('longitude', '>=', $filters['lng_min']))
            ->when($filters['lng_max'] !== null, fn ($q) => $q->where('longitude', '<=', $filters['lng_max']))
            // Urut berdasarkan urutan input: data lama di atas, data baru di paling bawah.
            // Ganti ke ->orderByDesc('id') kalau mau data terbaru di paling atas,
            // atau ->orderBy('name') untuk urut abjad.
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $countries = Location::query()->distinct()->orderBy('country')->pluck('country');

        return view('admin.locations', compact('locations', 'filters', 'countries'));
    }

    public function store(StoreLocationRequest $request, AuditService $audit): RedirectResponse
    {
        $location = Location::create($request->validated());
        $audit->log($request->user(), 'location.created', $location, ['code' => $location->code]);

        return back()->with('success', 'Location added.');
    }

    public function update(UpdateLocationRequest $request, Location $location, AuditService $audit): RedirectResponse
    {
        $location->fill($request->validated())->save();
        $audit->log($request->user(), 'location.updated', $location, $location->getChanges());

        return back()->with('success', 'Location updated.');
    }

    public function toggle(Request $request, Location $location, AuditService $audit): RedirectResponse
    {
        $location->is_active = ! $location->is_active;
        $location->save();
        $audit->log($request->user(), 'location.toggled', $location, ['is_active' => $location->is_active]);

        return back()->with('success', 'Location updated.');
    }

    public function destroy(Request $request, Location $location, AuditService $audit): RedirectResponse
    {
        // shipments.origin_id / destination_id are RESTRICT (soft-deleted shipments still count).
        $used = Shipment::withTrashed()->where(fn ($q) => $q->where('origin_id', $location->id)->orWhere('destination_id', $location->id))->exists();
        if ($used) {
            return back()->with('error', 'This location is used by shipments and cannot be deleted. Deactivate it instead.');
        }

        $audit->log($request->user(), 'location.deleted', $location, ['code' => $location->code, 'name' => $location->name]);
        $location->delete();

        return back()->with('success', 'Location deleted.');
    }
}