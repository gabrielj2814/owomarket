<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'shipping_rates';

    protected $guarded = [];

    protected $casts = [
        'min_value' => 'float',
        'max_value' => 'float',
        'cost' => 'float',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
