<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['shipment_id', 'user_id', 'order_id', 'amount', 'status', 'payment_type', 'snap_token', 'redirect_url'];

    protected function casts(): array
    {
        return ['raw_notification' => 'array', 'paid_at' => 'datetime', 'settled_at' => 'datetime'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }
}
