<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'countries'    => 'array',
        'states'       => 'array',
        'postal_codes' => 'array',
        'priority'     => 'integer',
        'is_active'    => 'boolean',
    ];

    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'shipping_zone_id');
    }
}

