@php
    $dt = fn ($v) => $v ? $v->format('Y-m-d\TH:i') : '';
    $rows = old('items') ?? ($shipment->exists
        ? $shipment->items->map(fn ($i) => ['description' => $i->description, 'quantity' => $i->quantity, 'weight_kg' => $i->weight_kg])->all()
        : []);
    $rows = array_pad(array_values($rows), 3, []);
@endphp
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="label">Customer *</label>
        <select class="input" name="customer_id" required>
            <option value="">Select customer</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" @selected((string) old('customer_id', $shipment->customer_id) === (string) $c->id)>{{ $c->name }} ({{ $c->email }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Reference number (PO / booking)</label>
        <input class="input" type="text" name="reference_number" maxlength="50" value="{{ old('reference_number', $shipment->reference_number) }}">
    </div>
    <div>
        <label class="label">Carrier *</label>
        <select class="input" name="carrier_id" required>
            <option value="">Select carrier</option>
            @foreach($carriers as $c)
                <option value="{{ $c->id }}" @selected((string) old('carrier_id', $shipment->carrier_id) === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Warehouse</label>
        <select class="input" name="warehouse_id">
            <option value="">No warehouse</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" @selected((string) old('warehouse_id', $shipment->warehouse_id) === (string) $wh->id)>{{ $wh->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Cargo type *</label>
        <select class="input" name="cargo_type" required>
            @foreach($cargoTypes as $k => $v)
                <option value="{{ $k }}" @selected(old('cargo_type', $shipment->cargo_type) === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Origin *</label>
        <select class="input" name="origin_id" required>
            <option value="">Select origin</option>
            @foreach($locations as $l)
                <option value="{{ $l->id }}" @selected((string) old('origin_id', $shipment->origin_id) === (string) $l->id)>{{ $l->name }} ({{ $l->code }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Destination *</label>
        <select class="input" name="destination_id" required>
            <option value="">Select destination</option>
            @foreach($locations as $l)
                <option value="{{ $l->id }}" @selected((string) old('destination_id', $shipment->destination_id) === (string) $l->id)>{{ $l->name }} ({{ $l->code }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Weight (kg) *</label>
        <input class="input" type="number" step="0.01" min="0.01" name="weight_kg" required value="{{ old('weight_kg', $shipment->weight_kg) }}">
    </div>
    <div>
        <label class="label">Volume (m&sup3;)</label>
        <input class="input" type="number" step="0.01" min="0.01" name="volume_cbm" value="{{ old('volume_cbm', $shipment->volume_cbm) }}">
    </div>
    <div>
        <label class="label">Expected departure <span class="text-red-600">*</span></label>
        <input class="input" type="datetime-local" data-utc-local name="expected_departure_at" required value="{{ old('expected_departure_at', $dt($shipment->expected_departure_at)) }}">
    </div>
    <div>
        <label class="label">Expected arrival <span class="text-red-600">*</span></label>
        <input class="input" type="datetime-local" data-utc-local name="expected_arrival_at" required value="{{ old('expected_arrival_at', $dt($shipment->expected_arrival_at)) }}">
    </div>
    <p class="text-xs text-ink-500 md:col-span-2" style="margin-top:-.5rem"><span>Times follow the time zone selected in the top bar</span>: <b data-no-i18n data-tz-name>UTC</b>. <span>They are saved in UTC.</span></p>
</div>

<h3 class="mt-6 font-semibold">Cargo items *</h3>
<p class="mb-2 text-xs text-ink-500">Enter what is being shipped (item name), e.g. “Songket cloth, 1 box”. At least one item is required.</p>
<div class="space-y-2">
    @foreach($rows as $i => $row)
        <div class="grid grid-cols-6 gap-2">
            <input class="input col-span-3" type="text" name="items[{{ $i }}][description]" placeholder="Item name" value="{{ $row['description'] ?? '' }}">
            <input class="input" type="number" min="1" name="items[{{ $i }}][quantity]" placeholder="Qty" value="{{ $row['quantity'] ?? '' }}">
            <input class="input col-span-2" type="number" step="0.01" min="0.01" name="items[{{ $i }}][weight_kg]" placeholder="Weight kg" value="{{ $row['weight_kg'] ?? '' }}">
        </div>
    @endforeach
</div>

@push('scripts')
<script>
(function () {
    // Jam yang diketik = jam di zona waktu yang dipilih di topbar (mis. WIB). Disimpan ke server sebagai UTC.
    var inputs = document.querySelectorAll('input[data-utc-local]');
    if (!inputs.length || !window.Intl) { return; }
    function zone() { try { return (window.CT && CT.tz && CT.tz()) || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'; } catch (e) { return 'UTC'; } }
    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) { return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()) + 'T' + pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()); }
    function parse(v) { var m = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/.exec(v || ''); return m ? Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) : null; }
    function offset(ts, tz) { // selisih (ms) zona tz terhadap UTC pada saat ts
        var t = Math.floor(ts / 1000) * 1000, p = {};
        new Intl.DateTimeFormat('en-US', { timeZone: tz, hourCycle: 'h23', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' })
            .formatToParts(new Date(t)).forEach(function (x) { p[x.type] = x.value; });
        return Date.UTC(+p.year, +p.month - 1, +p.day, (+p.hour) % 24, +p.minute, +p.second) - t;
    }
    function toLocal(v, tz) { var ts = parse(v); return ts === null ? v : fmt(new Date(ts + offset(ts, tz))); }
    function toUtc(v, tz) { var wall = parse(v); if (wall === null) { return v; } var ts = wall - offset(wall, tz); ts = wall - offset(ts, tz); return fmt(new Date(ts)); }
    function names(tz) { document.querySelectorAll('[data-tz-name]').forEach(function (el) { el.textContent = (window.CT && CT.tzLabel) ? CT.tzLabel() : tz; }); }

    var tz = zone();
    inputs.forEach(function (i) { if (i.value) { i.value = toLocal(i.value, tz); } });
    names(tz);

    // Ganti negara/zona waktu saat mengisi: pertahankan waktu yang sama, tampilkan di zona baru.
    document.addEventListener('ct:country', function () {
        var next = zone();
        if (next === tz) { names(next); return; }
        inputs.forEach(function (i) { if (i.value) { i.value = toLocal(toUtc(i.value, tz), next); } });
        tz = next; names(tz);
    });

    var form = inputs[0].form;
    if (form) {
        form.addEventListener('submit', function () { inputs.forEach(function (i) { if (i.value) { i.value = toUtc(i.value, tz); } }); });
    }
    window.addEventListener('pageshow', function (e) { if (e.persisted) { location.reload(); } });
})();
</script>
@endpush
