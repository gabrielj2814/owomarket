<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * El expediente de entrega de un pedido de tienda.
 *
 * Vive en el modulo Monetization y no en Order o Shipment a proposito: su unica razon de
 * existir es decidir **cuando el dinero de una venta deja de estar retenido**, y quien manda
 * sobre eso es `PlatformCommission`. Ponerlo junto a lo que gobierna evita tener que
 * preguntarse mas adelante quien es el dueño de la regla.
 */
class OrderDeliveryConfirmation extends Model
{
    use HasUuids;

    protected $table = 'order_delivery_confirmations';

    /**
     * Misma resolucion de conexion que `PlatformCommission` y `CentralProduct`: esta tabla es
     * central, pero se escribe tambien desde el contexto de una tienda --el comerciante
     * declara la entrega desde su propia API-- y en tests todo vive en la misma base.
     */
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
        'customer_id',
        'declared_delivered_at',
        'confirmed_at',
        'released_by',
        'released_at',
        'shipment_evidence',
        'confirmation_evidence',
    ];

    protected $casts = [
        'declared_delivered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'released_at' => 'datetime',
        'shipment_evidence' => 'array',
        'confirmation_evidence' => 'array',
    ];
}
