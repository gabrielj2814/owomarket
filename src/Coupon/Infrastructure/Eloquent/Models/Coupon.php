<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'coupons';

    protected $guarded = [];

    protected $casts = [
        'value' => 'float',
        'min_order_amount' => 'float',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'used_count' => 'integer',
        'valid_from' => 'date:Y-m-d',
        'valid_to' => 'date:Y-m-d',
        'is_active' => 'boolean',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
    ];
}
