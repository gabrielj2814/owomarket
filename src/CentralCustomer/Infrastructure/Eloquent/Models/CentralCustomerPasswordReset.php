<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentralCustomerPasswordReset extends Model
{
    use HasFactory, HasUuids;

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $table = 'central_customer_password_resets';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'email',
        'pin_code',
        'token',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
