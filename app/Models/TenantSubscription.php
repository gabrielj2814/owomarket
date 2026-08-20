<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

class TenantSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenant_subscriptions';

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
        'plan_id',
        'billing_cycle',
        'status',
        'starts_at',
        'ends_at',
        'renews_at',
        'cancelled_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'renews_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true)
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
