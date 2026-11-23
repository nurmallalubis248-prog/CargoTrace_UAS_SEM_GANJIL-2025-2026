<?php

namespace App\Http\Middleware;

use App\Services\SimulationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setiap halaman yang dibuka ikut "mengejar jadwal" pengiriman (maks. sekali per 15 detik), sehingga pengiriman tetap
 * berjalan sesuai jam walaupun scheduler tidak menyala atau aplikasi/laptop sempat mati. Tidak pernah merusak halaman.
 */
class CatchUpSimulation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('cargotrace.simulation.enabled') && Cache::add('cargotrace.sim.throttle', 1, 15)) {
            try {
                app(SimulationService::class)->catchUp();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
