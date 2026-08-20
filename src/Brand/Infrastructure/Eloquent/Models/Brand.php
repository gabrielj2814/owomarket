<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    protected $guarded = [];

    protected $casts = [
        'central_uuid' => 'string',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];
}
