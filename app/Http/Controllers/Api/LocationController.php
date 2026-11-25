<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /** Paginated list. Filters: q (name or code), type, country (2 letters), per_page (1-100, default 25). */
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(Location::TYPES)],
            'country' => ['nullable', 'string', 'size:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $locations = Location::query()
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$s.'%')->orWhere('code', 'like', '%'.$s.'%')))
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($f['country'] ?? null, fn ($q, $c) => $q->where('country', strtoupper($c)))
            ->orderBy('name')
            ->paginate((int) ($f['per_page'] ?? 25))
            ->withQueryString();

        return LocationResource::collection($locations);
    }

    public function store(StoreLocationRequest $request, AuditService $audit): JsonResponse
    {
        $location = Location::create($request->validated());
        $audit->log($request->user(), 'location.created', $location, ['code' => $location->code]);

        return (new LocationResource($location))->response()->setStatusCode(201);
    }
}
