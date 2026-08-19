<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Invoice extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'invoices';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'customer_id',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'subtotal_ves',
        'total_ves',
        'subtotal_usd',
        'total_usd',
        'commission_amount',
        'commission_currency',
        'payment_method',
        'payment_status',
        'paid_at',
        'billing_customer_name',
        'billing_customer_tax_id',
        'billing_customer_email',
        'billing_customer_address',
        'issuer_snapshot',
        'pdf_path',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'float',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
        'subtotal_ves' => 'float',
        'total_ves' => 'float',
        'subtotal_usd' => 'float',
        'total_usd' => 'float',
        'commission_amount' => 'float',
        'paid_at' => 'datetime',
        'billing_customer_address' => 'array',
        'issuer_snapshot' => 'array',
        'metadata' => 'array',
    ];

    /**
     * @return HasMany<InvoiceItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }
}
