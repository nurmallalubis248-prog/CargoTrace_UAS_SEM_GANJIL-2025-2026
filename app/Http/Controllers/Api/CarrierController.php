<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarrierRequest;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

class CarrierController extends Controller
{
    public function index()
    {
        return CarrierResource::collection(Carrier::query()->orderBy('name')->get());
    }

    public function store(StoreCarrierRequest $request, AuditService $audit): JsonResponse
    {
        $carrier = Carrier::create($request->validated());
        $audit->log($request->user(), 'carrier.created', $carrier, ['code' => $carrier->code]);

        return (new CarrierResource($carrier))->response()->setStatusCode(201);
    }
}
