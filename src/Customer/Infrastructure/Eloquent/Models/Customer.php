<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'customers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'birth_date',
        'gender',
        'is_active',
        'accepts_marketing',
        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
        'accepts_marketing' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * @return MorphMany<Address>
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
