<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function tenantUsers()
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Tell VirtualColumn which attributes are real DB columns
     * so they are not moved into the JSON `data` field.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'status',
            'theme',
            'locale',
            'timezone',
            'currency',
            'created_at',
            'updated_at',
        ];
    }
}
