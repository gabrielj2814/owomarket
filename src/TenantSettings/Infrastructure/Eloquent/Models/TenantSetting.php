<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TenantSetting extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'tenant_settings';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'key',
        'value',
        'type',
        'group',
    ];

    protected $casts = [
        'key' => 'string',
        'value' => 'string',
        'type' => 'string',
        'group' => 'string',
    ];
}
