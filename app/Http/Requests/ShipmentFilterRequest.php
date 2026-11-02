<?php

namespace App\Http\Requests;

use App\Enums\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipmentFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ShipmentStatus::class)],
            'carrier_id' => ['nullable', 'integer'],
            'origin_id' => ['nullable', 'integer'],
            'destination_id' => ['nullable', 'integer'],
            'severity' => ['nullable', Rule::in(['LOW', 'MEDIUM', 'HIGH'])],
            'delayed' => ['nullable', Rule::in(['0', '1', 'true', 'false'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', Rule::in(\App\Models\Shipment::SORTABLE)],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
