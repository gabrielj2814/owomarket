<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'base_currency',
        'target_currency',
        'rate',
        'source',
        'rate_date',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'rate' => 'float',
        'rate_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
