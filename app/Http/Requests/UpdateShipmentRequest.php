<?php

namespace App\Http\Requests;

use App\Enums\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('update', $this->route('shipment'));
    }

    protected function prepareForValidation(): void
    {
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
        $sometimes = $this->isMethod('PATCH') ? 'sometimes' : 'required';
        // A PENDING customer request has no schedule yet: optional while editing, required once it is approved.
        $pending = $this->route('shipment')?->current_status === ShipmentStatus::PENDING;
        $schedule = ($pending && ! $this->boolean('approve')) ? 'nullable' : $sometimes;

        return [
            'reference_number' => ['nullable', 'string', 'max:50'],
            'customer_id' => [$sometimes, 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            'carrier_id' => [$sometimes, 'integer', Rule::exists('carriers', 'id')->where('is_active', true)],
            'origin_id' => [$sometimes, 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'destination_id' => [$sometimes, 'integer', 'different:origin_id', Rule::exists('locations', 'id')->where('is_active', true)],
            'cargo_type' => [$sometimes, Rule::in(array_keys(config('cargotrace.cargo_types')))],
            'weight_kg' => [$sometimes, 'numeric', 'gt:0', 'max:99999999'],
            'volume_cbm' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'expected_departure_at' => [$schedule, 'date'],
            'expected_arrival_at' => [$schedule, 'date', 'after:expected_departure_at'],
            // Form web: minimal 1 barang wajib diisi. API (dan PATCH parsial) tetap opsional seperti sebelumnya.
            'items' => ($this->is('api/*') || $this->isMethod('PATCH')) ? ['nullable', 'array', 'max:50'] : ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'gt:0'],
            'items.*.volume_cbm' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one cargo item (item name).',
            'items.min' => 'Add at least one cargo item (item name).',
        ];
    }
}
