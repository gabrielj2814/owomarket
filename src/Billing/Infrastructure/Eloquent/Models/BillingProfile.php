<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BillingProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'billing_profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'legal_name',
        'tax_id',
        'billing_email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'invoice_prefix',
        'next_invoice_number',
        'invoice_footer_notes',
        'logo_path',
        'metadata',
    ];

    protected $casts = [
        'next_invoice_number' => 'integer',
        'metadata' => 'array',
    ];
}
