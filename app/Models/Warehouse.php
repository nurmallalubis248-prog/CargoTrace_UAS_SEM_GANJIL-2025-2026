<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'location_id', 'capacity_kg', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /** Weight (kg) of shipments currently assigned and not yet delivered/cancelled. */
    public function loadKg(): float
    {
        return (float) $this->shipments()->whereNotIn('current_status', ['DELIVERED', 'CANCELLED'])->sum('weight_kg');
    }
}
