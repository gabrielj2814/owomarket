<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CentralBrand extends Model
{
    use HasUuids;

    protected $connection = 'central';

    protected $table = 'central_brands';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];
}
