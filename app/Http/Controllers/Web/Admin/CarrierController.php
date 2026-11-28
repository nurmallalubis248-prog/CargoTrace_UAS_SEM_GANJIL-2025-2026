<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarrierRequest;
use App\Http\Requests\UpdateCarrierRequest;
use App\Models\Carrier;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarrierController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'code' => strtoupper(trim((string) $request->query('code', ''))),
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '',
        ];

        $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';

        $carriers = Carrier::withCount('shipments')
            ->when($filters['name'] !== '', fn ($q) => $q->where('name', 'like', $like($filters['name'])))
            ->when($filters['code'] !== '', fn ($q) => $q->where('code', 'like', $like($filters['code'])))
            ->when($filters['status'] !== '', fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            // Urut berdasarkan urutan input: data lama di atas, data baru di paling bawah.
            // Ganti ke ->orderByDesc('id') kalau mau data terbaru di paling atas,
            // atau ->orderBy('name') untuk urut abjad.
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.carriers', ['carriers' => $carriers, 'filters' => $filters]);
    }

    public function store(StoreCarrierRequest $request, AuditService $audit): RedirectResponse
    {
        $carrier = Carrier::create($request->validated());
        $audit->log($request->user(), 'carrier.created', $carrier, ['code' => $carrier->code]);

        return back()->with('success', 'Carrier added.');
    }

    public function update(UpdateCarrierRequest $request, Carrier $carrier, AuditService $audit): RedirectResponse
    {
        $carrier->fill($request->validated())->save();
        $audit->log($request->user(), 'carrier.updated', $carrier, $carrier->getChanges());

        return back()->with('success', 'Carrier updated.');
    }

    public function toggle(Request $request, Carrier $carrier, AuditService $audit): RedirectResponse
    {
        $carrier->is_active = ! $carrier->is_active;
        $carrier->save();
        $audit->log($request->user(), 'carrier.toggled', $carrier, ['is_active' => $carrier->is_active]);

        return back()->with('success', 'Carrier updated.');
    }

    public function destroy(Request $request, Carrier $carrier, AuditService $audit): RedirectResponse
    {
        // shipments.carrier_id is RESTRICT: a carrier that already has shipments must be deactivated, not deleted.
        if ($carrier->shipments()->withTrashed()->exists()) {
            return back()->with('error', 'This carrier has shipments and cannot be deleted. Deactivate it instead.');
        }

        $audit->log($request->user(), 'carrier.deleted', $carrier, ['code' => $carrier->code, 'name' => $carrier->name]);
        $carrier->delete();

        return back()->with('success', 'Carrier deleted.');
    }
}