<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CentralCustomer extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    public function getConnectionName()
    {
        return app()->environment('testing') ? config('database.default') : 'central';
    }

    protected $table = 'central_customers';

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'password',
        'document_id',
        'avatar',
        'is_active',
        'email_verified_at',
        'metadata',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CentralCustomerAddress::class, 'customer_id', 'id');
    }

    public function ssoTokens(): HasMany
    {
        return $this->hasMany(CentralCustomerSsoToken::class, 'customer_id', 'id');
    }
}
