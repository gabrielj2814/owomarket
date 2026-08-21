<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;

class Cart extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cart_data' => 'array',
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
