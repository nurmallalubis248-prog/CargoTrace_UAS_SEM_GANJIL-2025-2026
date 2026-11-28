<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pengiriman otomatis ikut mengejar jadwal tiap ada yang membuka aplikasi (lihat SimulationService::catchUp).
        $middleware->web(append: [
            \App\Http\Middleware\CatchUpSimulation::class,
            \App\Http\Middleware\ReadOnlyViewer::class,
        ]);
        // Mode Viewer harus berjalan SEBELUM middleware `auth` (urutan prioritas bawaan Laravel akan memajukan `auth`).
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \App\Http\Middleware\ReadOnlyViewer::class,
        );
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // All /api/* errors are rendered as JSON with a consistent {"message": ...} shape
        // (no stack traces, no model class names).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });
    })
    ->create();
