<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index()
    {
        return LocationResource::collection(Location::query()->orderBy('name')->get());
    }

    public function store(StoreLocationRequest $request, AuditService $audit): JsonResponse
    {
        $location = Location::create($request->validated());
        $audit->log($request->user(), 'location.created', $location, ['code' => $location->code]);

        return (new LocationResource($location))->response()->setStatusCode(201);
    }
}
