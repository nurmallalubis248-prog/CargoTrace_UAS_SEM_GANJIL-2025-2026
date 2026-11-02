<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    /** Staff only (route middleware): list customers so shipments can be assigned to them. */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => User::query()->where('role', User::ROLE_CUSTOMER)->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }
}
