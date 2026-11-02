<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public const TYPES = ['PORT', 'AIRPORT', 'WAREHOUSE', 'OTHER'];

    protected $fillable = ['name', 'code', 'country', 'type', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
