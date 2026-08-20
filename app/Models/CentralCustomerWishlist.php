<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CentralCustomerWishlist extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'central_customer_wishlists';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'customer_id',
        'product_id',
        'tenant_id',
        'product_name',
        'product_slug',
        'product_price',
        'product_image',
    ];

    protected $casts = [
        'product_price' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CentralCustomer::class, 'customer_id', 'id');
    }
}
