<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tax_rates';

    protected $guarded = [];

    protected $casts = [
        'rate' => 'float',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];
}
