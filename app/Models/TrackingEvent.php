<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracking events are historical facts: they are only ever inserted, never updated.
 */
class TrackingEvent extends Model
{
    public const TYPE_STATUS_UPDATE = 'STATUS_UPDATE';
    public const TYPE_CHECKPOINT = 'CHECKPOINT';
    public const TYPE_ETA_UPDATE = 'ETA_UPDATE';
    public const TYPE_EXCEPTION = 'EXCEPTION';

    protected $fillable = [
        'shipment_id', 'event_type', 'status', 'location_id', 'location_label',
        'latitude', 'longitude', 'occurred_at', 'description', 'is_exception', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'occurred_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_exception' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Immutable history: block any attempt to overwrite an existing event.
        static::updating(fn () => false);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
