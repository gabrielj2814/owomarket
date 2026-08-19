<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'subscription_plans';

    public function getConnectionName()
    {
        return app()->environment('testing') ? config('database.default') : 'central';
    }

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'commission_rate',
        'features',
        'max_products',
        'is_active',
    ];

    protected $casts = [
        'price_monthly' => 'float',
        'price_yearly' => 'float',
        'commission_rate' => 'float',
        'features' => 'array',
        'max_products' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id', 'id');
    }
}
