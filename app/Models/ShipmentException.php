<?php

namespace App\Models;

use App\Enums\ExceptionStatus;
use App\Enums\ExceptionType;
use App\Enums\Severity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentException extends Model
{
    protected $table = 'shipment_exceptions';

    // status / resolved_at / detected_at / reported_by are system-controlled (see ExceptionService).
    protected $fillable = ['type', 'severity', 'description'];

    protected function casts(): array
    {
        return [
            'type' => ExceptionType::class,
            'severity' => Severity::class,
            'status' => ExceptionStatus::class,
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [ExceptionStatus::OPEN->value, ExceptionStatus::INVESTIGATING->value]);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
