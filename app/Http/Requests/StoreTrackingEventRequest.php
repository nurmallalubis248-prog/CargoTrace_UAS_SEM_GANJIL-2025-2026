<?php

namespace App\Http\Requests;

use App\Enums\ShipmentStatus;
use App\Models\TrackingEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTrackingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('addTracking', $this->route('shipment'));
    }

    public function rules(): array
    {
        return [
            // EXCEPTION events are created only through the exception workflow.
            'event_type' => ['required', Rule::in([
                TrackingEvent::TYPE_STATUS_UPDATE, TrackingEvent::TYPE_CHECKPOINT, TrackingEvent::TYPE_ETA_UPDATE,
            ])],
            'status' => ['required_if:event_type,'.TrackingEvent::TYPE_STATUS_UPDATE, 'nullable', Rule::enum(ShipmentStatus::class)],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'occurred_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'estimated_arrival_at' => ['nullable', 'date', 'required_if:event_type,'.TrackingEvent::TYPE_ETA_UPDATE],
        ];
    }
}
