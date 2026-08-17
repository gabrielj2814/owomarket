<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_reviews';

    protected $guarded = [];

    protected $casts = [
        'rating' => 'integer',
        'responded_at' => 'datetime',
        'is_approved' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
