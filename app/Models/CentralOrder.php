<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentralOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'central_orders';

    public function getConnectionName()
    {
        return app()->environment('testing') ? config('database.default') : 'central';
    }

    protected $fillable = [
        'id',
        'order_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_document_id',
        'shipping_address',
        'payment_method',
        'payment_details',
        'subtotal',
        'shipping_amount',
        'discount_amount',
        'total',
        'currency',
        'status',
        'payment_status',
        'metadata',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'payment_details' => 'array',
        'metadata' => 'array',
        'subtotal' => 'float',
        'shipping_amount' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CentralCustomer::class, 'customer_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CentralOrderItem::class, 'central_order_id', 'id');
    }
}
