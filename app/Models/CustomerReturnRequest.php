<?php

declare(strict_types=1);

namespace App\Models;

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
        return app()->environment('testing') ? config('database.default') : 'central';
    }

    protected $fillable = [
        'id',
        'order_id',
        'order_number',
        'customer_id',
        'customer_email',
        'product_id',
        'product_name',
        'tenant_id',
        'reason',
        'description',
        'photos',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CentralCustomer::class, 'customer_id', 'id');
    }
}
