<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    public const SORTABLE = [
        'shipment_number', 'expected_departure_at', 'expected_arrival_at',
        'estimated_arrival_at', 'current_status', 'delay_hours', 'created_at',
    ];

    // Only user-supplied master data is mass-assignable.
    // shipment_number, created_by, current_status, ETA/delay and current_* fields are set by services.
    protected $fillable = [
        'reference_number', 'customer_id', 'carrier_id', 'origin_id', 'destination_id', 'warehouse_id',
        'cargo_type', 'weight_kg', 'volume_cbm', 'expected_departure_at', 'expected_arrival_at',
    ];

    protected function casts(): array
    {
        return [
            'current_status' => ShipmentStatus::class,
            'weight_kg' => 'float',
            'volume_cbm' => 'float',
            'expected_departure_at' => 'datetime',
            'expected_arrival_at' => 'datetime',
            'estimated_arrival_at' => 'datetime',
            'actual_arrival_at' => 'datetime',
            'last_event_at' => 'datetime',
            'current_latitude' => 'float',
            'current_longitude' => 'float',
            'is_delayed' => 'boolean',
            'delay_hours' => 'float',
        ];
    }

    // ---- Relationships ----------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class);
    }

    // ---- Scopes -------------------------------------------------------------

    /** Customers only see their own shipments; staff see everything. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isCustomer()) {
            $query->where('shipments.customer_id', $user->id);
        }

        return $query;
    }

    /**
     * Why is this shipment late? Taken from its exceptions: an unresolved one first, otherwise the latest.
     * Returns null when the shipment is not delayed or no exception explains the delay yet.
     */
    public function delayReason(): ?array
    {
        if (! $this->is_delayed) {
            return null;
        }
        $all = $this->relationLoaded('exceptions') ? $this->exceptions : $this->exceptions()->get();
        $pick = $all->whereNull('resolved_at')->sortByDesc('detected_at')->first()
            ?? $all->sortByDesc('detected_at')->first();

        return $pick ? [
            'type' => $pick->type,
            'label' => $pick->type->label(),
            'description' => $pick->description,
            'open' => $pick->resolved_at === null,
        ] : null;
    }

    /** Server-side search & filter. All values are bound parameters (SQL-injection safe). */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when(! empty($f['q']), function (Builder $q) use ($f) {
                $term = '%'.$f['q'].'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('shipments.shipment_number', 'like', $term)
                        ->orWhere('shipments.reference_number', 'like', $term);
                });
            })
            ->when(! empty($f['status']), fn (Builder $q) => $q->where('shipments.current_status', $f['status']))
            ->when(! empty($f['carrier_id']), fn (Builder $q) => $q->where('shipments.carrier_id', $f['carrier_id']))
            ->when(! empty($f['origin_id']), fn (Builder $q) => $q->where('shipments.origin_id', $f['origin_id']))
            ->when(! empty($f['destination_id']), fn (Builder $q) => $q->where('shipments.destination_id', $f['destination_id']))
            ->when(! empty($f['severity']), fn (Builder $q) => $q->where('shipments.delay_severity', $f['severity']))
            ->when(isset($f['delayed']) && $f['delayed'] !== '', fn (Builder $q) => $q->where(
                'shipments.is_delayed',
                filter_var($f['delayed'], FILTER_VALIDATE_BOOLEAN)
            ))
            ->when(($f['exception'] ?? '') === 'open', fn (Builder $q) => $q->whereHas('exceptions', fn ($e) => $e->open()))
            ->when(($f['exception'] ?? '') === 'any', fn (Builder $q) => $q->has('exceptions'))
            ->when(! empty($f['date_from']), fn (Builder $q) => $q->whereDate('shipments.expected_arrival_at', '>=', $f['date_from']))
            ->when(! empty($f['date_to']), fn (Builder $q) => $q->whereDate('shipments.expected_arrival_at', '<=', $f['date_to']));
    }

    /** Whitelisted sorting (never interpolate user input into ORDER BY). */
    public function scopeSortBy(Builder $query, ?string $column, ?string $direction): Builder
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy('shipments.'.$column, $direction)->orderBy('shipments.id', 'desc');
    }
}
