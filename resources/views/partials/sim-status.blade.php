{{-- Peringatan untuk admin/operator hanya bila pengejaran jadwal simulasi gagal (galat terakhir disimpan di cache) --}}
@php
    $simErr = (config('cargotrace.simulation.enabled') && auth()->check() && auth()->user()->isStaff())
        ? \Illuminate\Support\Facades\Cache::get('cargotrace.sim.error') : null;
@endphp
@if($simErr)
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">Simulation error</p>
        <p class="mt-1" data-no-i18n>{{ $simErr }}</p>
    </div>
@endif
