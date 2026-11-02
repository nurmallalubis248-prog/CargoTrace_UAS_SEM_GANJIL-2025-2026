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
        <label class="label">Expected departure (UTC) *</label>
        <input class="input" type="datetime-local" name="expected_departure_at" required value="{{ old('expected_departure_at', $dt($shipment->expected_departure_at)) }}">
    </div>
    <div>
        <label class="label">Expected arrival (UTC) *</label>
        <input class="input" type="datetime-local" name="expected_arrival_at" required value="{{ old('expected_arrival_at', $dt($shipment->expected_arrival_at)) }}">
    </div>
</div>

<h3 class="mt-6 mb-2 font-semibold">Cargo items (optional)</h3>
<div class="space-y-2">
    @foreach($rows as $i => $row)
        <div class="grid grid-cols-6 gap-2">
            <input class="input col-span-3" type="text" name="items[{{ $i }}][description]" placeholder="Description" value="{{ $row['description'] ?? '' }}">
            <input class="input" type="number" min="1" name="items[{{ $i }}][quantity]" placeholder="Qty" value="{{ $row['quantity'] ?? '' }}">
            <input class="input col-span-2" type="number" step="0.01" min="0.01" name="items[{{ $i }}][weight_kg]" placeholder="Weight kg" value="{{ $row['weight_kg'] ?? '' }}">
        </div>
    @endforeach
</div>
