<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Product\Infrastructure\Eloquent\Models\Product;

class Wishlist extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'wishlist_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlist_items')
            ->withTimestamps();
    }
}
