<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

class CentralProduct extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'central_products';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'tenant_id',
        'tenant_product_id',
        'name',
        'slug',
        'description',
        'sku',
        'barcode',
        'price',
        'compare_price',
        'cost_price',
        'quantity',
        'is_visible',
        'is_blocked_by_admin',
        'is_featured',
        'category_name',
        'brand_name',
        'images',
        'variants',
        'specifications',
        'metadata',
    ];

    protected $casts = [
        'price' => 'float',
        'compare_price' => 'float',
        'cost_price' => 'float',
        'quantity' => 'integer',
        'is_visible' => 'boolean',
        'is_blocked_by_admin' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
        'variants' => 'array',
        'specifications' => 'array',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
