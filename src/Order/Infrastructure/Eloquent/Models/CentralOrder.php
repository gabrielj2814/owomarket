<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;

class CentralOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'central_orders';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'order_number',
        // Hallazgo C2: clave de idempotencia del checkout. Dos envíos del mismo
        // pedido con la misma clave devuelven el mismo CentralOrder.
        'idempotency_key',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_document_id',
        'shipping_address',
        'payment_method',
        'payment_details',
        'subtotal',
        'shipping_amount',
        'discount_amount',
        'total',
        'currency',
        'status',
        'payment_status',
        'metadata',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'payment_details' => 'array',
        'metadata' => 'array',
        'subtotal' => 'float',
        'shipping_amount' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CentralCustomer::class, 'customer_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CentralOrderItem::class, 'central_order_id', 'id');
    }

    /**
     * Hallazgo Auditoria #1: la clave real es `central_order_id`. Con `order_id` esto
     * devolvia SIEMPRE una coleccion vacia, porque ahi va el id del pedido de la tienda.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(PlatformCommission::class, 'central_order_id', 'id');
    }

    public function getTotalUsdAttribute(): float
    {
        return (float) $this->total;
    }

    public function getTotalVesAttribute(): ?float
    {
        return isset($this->metadata['total_ves']) ? (float) $this->metadata['total_ves'] : null;
    }

    public function getPaymentReferenceAttribute(): ?string
    {
        return $this->payment_details['reference']
            ?? $this->payment_details['payment_reference']
            ?? $this->metadata['payment_reference']
            ?? null;
    }

    public function getShippingTrackingNumberAttribute(): ?string
    {
        return $this->metadata['tracking_number'] ?? null;
    }

    public function getTenantIdAttribute(): ?string
    {
        return $this->metadata['tenant_id'] ?? $this->items()->first()?->tenant_id ?? null;
    }
}
