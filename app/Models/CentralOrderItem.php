<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

class CentralOrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'central_order_items';

    public function getConnectionName()
    {
        return app()->environment('testing') ? config('database.default') : 'central';
    }

    protected $fillable = [
        'id',
        'central_order_id',
        'tenant_id',
        'product_id',
        'product_name',
        'sku',
        'price',
        'quantity',
        'total',
        'tenant_order_id',
        'commission_rate',
        'commission_amount',
        'attributes',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'total' => 'float',
        'commission_rate' => 'float',
        'commission_amount' => 'float',
        'attributes' => 'array',
    ];

    public function centralOrder(): BelongsTo
    {
        return $this->belongsTo(CentralOrder::class, 'central_order_id', 'id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
