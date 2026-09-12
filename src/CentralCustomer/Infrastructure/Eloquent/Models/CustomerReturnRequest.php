<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReturnRequest extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'customer_return_requests';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'order_id',
        // 'central' | 'storefront': a que base apunta `order_id`. Ver la migracion
        // `add_order_source_to_customer_return_requests`.
        'order_source',
        'order_number',
        'tenant_order_id',
        'customer_id',
        'customer_email',
        'product_id',
        'product_name',
        'amount',
        'delivered_at',
        'tenant_id',
        'reason',
        'description',
        'photos',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'platform_covered_amount',
        'admin_notes',
    ];

    protected $casts = [
        'photos' => 'array',
        'amount' => 'float',
        'platform_covered_amount' => 'float',
        'delivered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /** Estados en los que la reclamacion sigue esperando a alguien. */
    public const ABIERTAS = ['requested', 'in_review'];

    /**
     * Estados que impiden abrir OTRA reclamacion sobre el mismo articulo.
     *
     * No es lo mismo que `ABIERTAS` --una aprobada ya no espera a nadie, pero tampoco admite
     * que se reclame el mismo articulo otra vez-- y por eso es su propia lista.
     *
     * Vive aqui, y no repetida, porque la usan dos sitios con papeles opuestos:
     * `CreateCustomerReturnRequestUseCase` la aplica al RECHAZAR una solicitud nueva, y
     * `ListStorefrontCustomerOrdersUseCase` al decidir si enseña el boton de reclamar. Si
     * divergieran, la pantalla ofreceria un boton que el backend rechaza --o escondería uno
     * que si funciona, que es la misma clase de mentira--.
     */
    public const BLOQUEAN_NUEVA = ['requested', 'in_review', 'approved'];

    /** Si esta reclamacion impide abrir otra sobre el mismo articulo. */
    public function bloqueaNueva(): bool
    {
        return in_array($this->status, self::BLOQUEAN_NUEVA, true);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::ABIERTAS, true);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CentralCustomer::class, 'customer_id', 'id');
    }
}
