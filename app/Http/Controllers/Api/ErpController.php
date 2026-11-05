<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Read-only integration endpoints for an external ERP / accounting system. */
class ErpController extends Controller
{
    public function shipments(Request $request): JsonResponse
    {
        $q = Shipment::with(['customer:id,name,email', 'carrier:id,name,code,transport_mode', 'origin:id,code,name,country', 'destination:id,code,name,country', 'warehouse:id,code,name'])
            ->when($request->query('updated_since'), fn ($q, $d) => $q->where('updated_at', '>=', $d))
            ->orderBy('id');

        return response()->json($q->paginate(min((int) $request->query('per_page', 50), 200)));
    }

    public function payments(Request $request): JsonResponse
    {
        $q = Payment::with('shipment:id,shipment_number')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', strtoupper($s)))
            ->orderBy('id');

        return response()->json($q->paginate(min((int) $request->query('per_page', 50), 200)));
    }
}
