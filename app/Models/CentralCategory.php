<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentralCategory extends Model
{
    use HasUuids;

    protected $connection = 'central';

    protected $table = 'tenant_categories';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'parent_id',
        'lft',
        'rgt',
        'depth',
        'is_active',
        'position',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'metadata' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position', 'asc');
    }
}
