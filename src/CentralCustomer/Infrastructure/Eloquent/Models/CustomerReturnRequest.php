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

    public function isOpen(): bool
    {
        return in_array($this->status, self::ABIERTAS, true);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CentralCustomer::class, 'customer_id', 'id');
    }
}
