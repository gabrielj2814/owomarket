<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Src\Attribute\Infrastructure\Eloquent\Models\ProductAttributeValue;

class ProductVariant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_variants';

    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
        'compare_price' => 'float',
        'cost_price' => 'float',
        'quantity' => 'integer',
        'weight' => 'float',
        'attributes' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_variant_attribute_values',
            'product_variant_id',
            'product_attribute_value_id'
        )->withTimestamps();
    }
}
