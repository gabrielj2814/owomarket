<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentralHomeBanner extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'central_home_banners';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'title',
        'subtitle',
        'image_url',
        'link_url',
        'badge_text',
        'position_type',
        'order_position',
        'is_active',
        'start_date',
        'end_date',
        'metadata',
    ];

    protected $casts = [
        'order_position' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
    ];
}
