<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de despacho de un pedido central hacia UNA tienda.
 *
 * Su razón de ser es el índice único `(central_order_id, tenant_id)`: es lo
 * que impide que un reintento del despacho cree un segundo pedido —y una
 * segunda comisión— en la misma tienda (hallazgo C2).
 */
class CentralOrderDispatch extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'central_order_dispatches';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'central_order_id',
        'tenant_id',
        'tenant_order_id',
        'status',
        'attempts',
        'error_message',
        'dispatched_at',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
