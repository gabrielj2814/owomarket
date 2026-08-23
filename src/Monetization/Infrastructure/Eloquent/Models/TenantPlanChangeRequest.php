<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Solicitud de cambio de plan de un comerciante (hallazgo T3).
 *
 * Vive aparte de `TenantSubscription` a proposito: una solicitud pendiente no debe aparecer
 * en ninguna consulta de suscripciones, y menos en la que calcula la comision.
 */
class TenantPlanChangeRequest extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenant_plan_change_requests';

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
        'current_plan_id',
        'requested_plan_id',
        'billing_cycle',
        'status',
        'requested_by_user_id',
        'notes',
        'resolved_by_user_id',
        'resolved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'requested_plan_id');
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'current_plan_id');
    }
}
