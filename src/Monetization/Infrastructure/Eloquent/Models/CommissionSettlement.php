<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

class CommissionSettlement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'commission_settlements';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'settlement_number',
        'tenant_id',
        'type',
        'total_orders_count',
        'gross_sales_amount',
        'commission_amount',
        'net_amount',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'settled_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'gross_sales_amount' => 'float',
        'commission_amount' => 'float',
        'net_amount' => 'float',
        'settled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PlatformCommission::class, 'settlement_id', 'id');
    }
}
