<?php

namespace App\Http\Requests;

use App\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('create', Shipment::class);
    }

    protected function prepareForValidation(): void
    {
        // Drop empty item rows coming from the HTML form.
        if (is_array($this->input('items'))) {
            $this->merge([
                'items' => collect($this->input('items'))
                    ->filter(fn ($row) => is_array($row) && trim((string) ($row['description'] ?? '')) !== '')
                    ->values()->all(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'reference_number' => ['nullable', 'string', 'max:50'],
            'customer_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            'warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'carrier_id' => ['required', 'integer', Rule::exists('carriers', 'id')->where('is_active', true)],
            'origin_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'destination_id' => ['required', 'integer', 'different:origin_id', Rule::exists('locations', 'id')->where('is_active', true)],
            'cargo_type' => ['required', Rule::in(array_keys(config('cargotrace.cargo_types')))],
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'volume_cbm' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'expected_departure_at' => ['required', 'date'],
            'expected_arrival_at' => ['required', 'date', 'after:expected_departure_at'],
            'items' => ['nullable', 'array', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'gt:0'],
            'items.*.volume_cbm' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
