<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
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
        'central_order_id',
        'order_number',
        'order_total',
        'commission_rate',
        'commission_amount',
        'currency',
        'exchange_rate',
        'status',
        'released_at',
        'settlement_id',
        'payment_gateway',
        'metadata',
    ];

    protected $casts = [
        'order_total' => 'float',
        'commission_rate' => 'float',
        'commission_amount' => 'float',
        'exchange_rate' => 'float',
        'released_at' => 'datetime',
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

    /**
     * Hallazgo Auditoria #1: esto apuntaba a `order_id`, que contiene el UUID del pedido
     * dentro de la base del INQUILINO. Como `central_orders.id` vive en la base central,
     * los dos identificadores nunca coinciden y la relacion devolvia null siempre — sin
     * error, que es lo que la hacia dificil de ver.
     *
     * `order_id` no se toca: sigue siendo el pedido de la tienda, que es lo que necesitan
     * los informes del comerciante.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CentralOrder::class, 'central_order_id', 'id');
    }
}
