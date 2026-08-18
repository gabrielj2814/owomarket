<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Address extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'addresses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'addressable_type',
        'addressable_id',
        'type',
        'first_name',
        'last_name',
        'company',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * @return MorphTo<Model, Address>
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
