{{-- Compact clock + calendar (single small component). Updates every second; formatted for the active UI language. --}}
<div id="clock-widget" class="flex items-center gap-3 rounded-xl border border-matcha-100 bg-white px-3 py-1.5 shadow-sm" title="{{ __('ui.common.local_time') }}">
    <div class="w-11 overflow-hidden rounded-lg border border-sakura-200 text-center leading-none">
        <div id="clock-month" class="bg-sakura-400 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">---</div>
        <div id="clock-day" class="bg-white py-1 text-base font-extrabold text-ink-800">--</div>
    </div>
    <div class="leading-tight">
        <div id="clock-time" class="text-sm font-bold tabular-nums text-matcha-800">--:--:--</div>
        <div class="text-[11px] text-ink-500"><span id="clock-weekday">---</span>, <span id="clock-date">---</span></div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var loc = document.documentElement.lang === 'id' ? 'id-ID' : 'en-GB';
    var el = function (id) { return document.getElementById(id); };
    function tick() {
        var n = new Date();
        el('clock-time').textContent = n.toLocaleTimeString(loc, {hour12: false});
        el('clock-weekday').textContent = n.toLocaleDateString(loc, {weekday: 'long'});
        el('clock-date').textContent = n.toLocaleDateString(loc, {day: 'numeric', month: 'long', year: 'numeric'});
        el('clock-month').textContent = n.toLocaleDateString(loc, {month: 'short'});
        el('clock-day').textContent = n.getDate();
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
@endpush
