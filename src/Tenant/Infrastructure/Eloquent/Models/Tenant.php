<?php

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, SoftDeletes;

    /**
     * Tarea 4 de la auditoria: esto fijaba `'central'` a mano mientras los otros 22 modelos
     * centrales leian `tenancy.database.central_connection`. Eran dos fuentes de verdad
     * para la misma pregunta. Ahora usa la misma que el resto.
     */
    public function getConnectionName()
    {
        if (app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    public $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    // public function tenantUsers()
    // {
    //     return $this->hasMany(TenantUser::class);
    // }

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
            'request',
            'created_at',
            'updated_at',
        ];
    }
}
