<?php

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantUser extends Model
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'tenant_users';

    protected $guarded = [];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

