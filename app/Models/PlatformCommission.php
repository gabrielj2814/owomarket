<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

class PlatformCommission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'platform_commissions';

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
        'order_id',
        'order_number',
        'order_total',
        'commission_rate',
        'commission_amount',
        'currency',
        'status',
        'settlement_id',
        'payment_gateway',
        'metadata',
    ];

    protected $casts = [
        'order_total' => 'float',
        'commission_rate' => 'float',
        'commission_amount' => 'float',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CommissionSettlement::class, 'settlement_id', 'id');
    }
}
