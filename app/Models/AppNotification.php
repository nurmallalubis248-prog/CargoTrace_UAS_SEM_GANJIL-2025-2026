<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** In-app notification (table: notifications). Named AppNotification to avoid clashing with Illuminate's Notification. */
class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['user_id', 'shipment_id', 'type', 'message'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
