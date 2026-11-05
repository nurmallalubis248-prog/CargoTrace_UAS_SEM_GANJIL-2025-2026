<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Protects /api/erp/* with a static key (header X-ERP-KEY). Configure ERP_API_KEY in .env. */
class ErpKey
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) config('services.erp.api_key');
        if ($key === '' || ! hash_equals($key, (string) $request->header('X-ERP-KEY'))) {
            return response()->json(['message' => 'Invalid or missing X-ERP-KEY.'], 401);
        }

        return $next($request);
    }
}
