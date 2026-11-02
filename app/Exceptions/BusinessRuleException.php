<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown by the service layer when a business rule is violated
 * (e.g. invalid status transition, event in the future).
 * API  -> 422 JSON {"message": ..., "errors": {"rule": [...]}}
 * Web  -> redirect back with a flash error (no stack trace, no 500).
 */
class BusinessRuleException extends RuntimeException
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'errors' => ['rule' => [$this->getMessage()]],
            ], 422);
        }

        return redirect()->back()->withInput()->with('error', $this->getMessage());
    }
}
