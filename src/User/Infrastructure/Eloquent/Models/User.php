<?php

declare(strict_types=1);

namespace Src\User\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantUser;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    public $table = 'users';

    public $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected string $guard_name = 'web';

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * `type` NO esta aqui a proposito (hallazgo Auditoria, tarea 3).
     *
     * Es el campo que concede privilegio: un `User::create($request->all())` en cualquier
     * endpoint publico permitiria enviar `type=super_admin`. Hoy ningun camino lo hace
     * —el alta publica fija `UserType::TENANT_OWNER` a mano— pero dejarlo asignable en
     * masa es dejar la puerta abierta al dia que alguien escriba ese `create()`.
     *
     * Quien tenga que fijarlo lo hace explicitamente, que ademas se lee mejor: se ve quien
     * decide el rol y donde.
     *
     * `is_active` si se queda: activarse la propia cuenta no escala privilegios, y quitarlo
     * solo daria trabajo sin cerrar nada.
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'user_id', 'id');
    }
}
