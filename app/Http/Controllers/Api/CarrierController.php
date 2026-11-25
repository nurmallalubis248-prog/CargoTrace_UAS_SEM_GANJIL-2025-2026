<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarrierRequest;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CarrierController extends Controller
{
    /** Paginated list. Filters: q (name or code), transport_mode (SEA|AIR|ROAD), per_page (1-100, default 25). */
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'transport_mode' => ['nullable', Rule::in(['SEA', 'AIR', 'ROAD'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $carriers = Carrier::query()
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$s.'%')->orWhere('code', 'like', '%'.$s.'%')))
            ->when($f['transport_mode'] ?? null, fn ($q, $m) => $q->where('transport_mode', $m))
            ->orderBy('name')
            ->paginate((int) ($f['per_page'] ?? 25))
            ->withQueryString();

        return CarrierResource::collection($carriers);
    }

    public function store(StoreCarrierRequest $request, AuditService $audit): JsonResponse
    {
        $carrier = Carrier::create($request->validated());
        $audit->log($request->user(), 'carrier.created', $carrier, ['code' => $carrier->code]);

        return (new CarrierResource($carrier))->response()->setStatusCode(201);
    }
}
